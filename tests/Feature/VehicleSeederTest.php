<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleVariant;
use Database\Seeders\VehicleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_seed_has_specs_and_real_webp_media_for_all_five_catalog_models(): void
    {
        $this->seed(VehicleSeeder::class);

        $this->assertSame(['vf5', 'vf6', 'vf7', 'vf8', 'vf9'],
            Vehicle::query()->orderBy('slug')->pluck('slug')->all());
        $this->assertDatabaseCount('vehicle_variants', 10);
        $this->assertDatabaseCount('media', 5);
        foreach (Vehicle::query()->with('media')->get() as $vehicle) {
            $specifications = $vehicle->specifications;
            $this->assertGreaterThanOrEqual(15, count($specifications));
            $this->assertSame('04/10/2026', $specifications['Ngày đối chiếu nguồn']);
            $this->assertStringStartsWith('https://vinfastauto.com/', $specifications['Nguồn thông số']);
            $this->assertNotNull($vehicle->media);
            $this->assertStringContainsString($vehicle->name, $vehicle->media->alt);
            $bytes = Storage::disk('local')->get($vehicle->media->path);
            $dimensions = getimagesizefromstring($bytes);
            $this->assertSame(IMAGETYPE_WEBP, $dimensions[2]);
            $this->assertSame($dimensions[0], $vehicle->media->width);
            $this->assertSame($dimensions[1], $vehicle->media->height);
            $this->assertSame(strlen($bytes), $vehicle->media->size);
            $this->assertLessThanOrEqual(1920, $vehicle->media->width);
        }
        $this->assertDatabaseHas('vehicle_colors', [
            'vehicle_id' => Vehicle::query()->where('slug', 'vf8')->firstOrFail()->id,
            'name' => 'Infinity Blanc',
        ]);
        $this->assertDatabaseHas('vehicle_colors', [
            'vehicle_id' => Vehicle::query()->where('slug', 'vf9')->firstOrFail()->id,
            'name' => 'Infinity Blanc',
        ]);
        $this->assertStringContainsString('không phải VF 8 Thế hệ mới 2026',
            Vehicle::query()->where('slug', 'vf8')->firstOrFail()->specifications['Phiên bản tham chiếu']);
        $this->assertSame('300 kW / 402 hp',
            Vehicle::query()->where('slug', 'vf9')->firstOrFail()->specifications['Công suất tối đa (brochure)']);
    }

    public function test_rerun_does_not_duplicate_or_overwrite_editor_data_and_stored_images(): void
    {
        $this->seed(VehicleSeeder::class);
        $vehicle = Vehicle::query()->where('slug', 'vf8')->firstOrFail();
        $manualMedia = Media::factory()->create();
        $vehicle->update([
            'name' => 'VF 8 do đại lý biên tập',
            'segment' => 'Phân khúc đã chỉnh',
            'description' => 'Mô tả riêng',
            'specifications' => ['Thông số riêng' => 'Đã đối chiếu xe tại showroom'],
            'media_id' => $manualMedia->id,
            'brochure_url' => 'https://example.com/brochure.pdf',
            'is_active' => false,
        ]);
        $variant = VehicleVariant::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
        $variant->update(['price' => 1000000000]);
        $color = VehicleColor::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
        $color->update(['media_id' => $manualMedia->id, 'hex' => '#fafafa']);
        $seedMedia = Media::query()->where('path', 'images/vf8.webp')->firstOrFail();
        $seedMedia->update(['alt' => 'Alt do người viết chỉnh']);
        $imageHash = hash('sha256', Storage::disk('local')->get('images/vf8.webp'));
        $original = $vehicle->fresh()->getAttributes();

        $this->seed(VehicleSeeder::class);

        $this->assertSame($original, $vehicle->fresh()->getAttributes());
        $this->assertSame('1000000000', $variant->fresh()->price);
        $this->assertSame($manualMedia->id, $color->fresh()->media_id);
        $this->assertSame('#fafafa', $color->fresh()->hex);
        $this->assertSame('Alt do người viết chỉnh', $seedMedia->fresh()->alt);
        $this->assertSame($imageHash, hash('sha256', Storage::disk('local')->get('images/vf8.webp')));
        $this->assertDatabaseCount('vehicles', 5);
        $this->assertDatabaseCount('vehicle_variants', 10);
        $this->assertDatabaseCount('vehicle_colors', 2);
        $this->assertDatabaseCount('media', 6);
    }

    public function test_seed_enriches_empty_existing_catalog_record_and_restores_missing_seed_file(): void
    {
        $vehicle = Vehicle::factory()->create([
            'slug' => 'vf5',
            'name' => 'Tên đã chỉnh',
            'segment' => null,
            'description' => null,
            'specifications' => [],
            'media_id' => null,
            'is_active' => false,
        ]);
        $this->seed(VehicleSeeder::class);

        $vehicle->refresh();
        $this->assertSame('Tên đã chỉnh', $vehicle->name);
        $this->assertFalse($vehicle->is_active);
        $this->assertNotEmpty($vehicle->description);
        $this->assertNotEmpty($vehicle->specifications);
        $this->assertNotNull($vehicle->media_id);
        $media = $vehicle->media;
        Storage::disk('local')->delete($media->path);

        $this->seed(VehicleSeeder::class);

        Storage::disk('local')->assertExists($media->path);
        $this->assertSame($media->id, $vehicle->fresh()->media_id);
        $this->assertDatabaseCount('media', 5);
    }
}
