<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use Database\Seeders\VehicleColorSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleColorSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_shipped_manifests_seed_verified_images_without_invented_color_codes(): void
    {
        $this->seed([VehicleSeeder::class, VehicleColorSeeder::class]);

        $this->assertDatabaseCount('vehicle_colors', 18);
        $this->assertDatabaseCount('media', 18);
        $this->assertDatabaseCount('users', 0);
        foreach ($this->catalog() as $slug => $entries) {
            $vehicle = Vehicle::query()->where('slug', $slug)->with('colors.media')->sole();
            $this->assertCount(count($entries), $vehicle->colors);
            foreach ($entries as $entry) {
                $this->assertSame('2026-10-05', $entry['verified_at']);
                $this->assertStringStartsWith('https://vinfastauto.com/', $entry['source']);
                $this->assertStringStartsWith('https://static-cms-prod.vinfastauto.com/', $entry['image_source']);
                $this->assertFileExists(public_path('assets/vinfast/'.$entry['image']));
                $color = $vehicle->colors->firstWhere('name', $entry['name']);
                $this->assertNotNull($color);
                $this->assertNull($color->hex);
                $this->assertNull($color->secondary_hex);
                $this->assertNotNull($color->media);
                $bytes = Storage::disk('local')->get($color->media->path);
                $dimensions = getimagesizefromstring($bytes);
                $this->assertSame(IMAGETYPE_WEBP, $dimensions[2]);
                $this->assertSame($dimensions[0], $color->media->width);
                $this->assertSame($dimensions[1], $color->media->height);
                $this->assertSame(strlen($bytes), $color->media->size);
                $this->assertLessThanOrEqual(1920, $color->media->width);
                $this->assertNotEmpty($color->media->alt);
            }
        }
    }

    public function test_rerun_preserves_manual_colors_media_alt_and_stored_file_contents(): void
    {
        $this->freezeTime();
        $this->seed([VehicleSeeder::class, VehicleColorSeeder::class]);
        $vehicle = Vehicle::query()->where('slug', 'vf5')->sole();
        $manualMedia = Media::factory()->create(['alt' => 'Manual image alt']);
        Storage::disk('local')->put($manualMedia->path, 'Manual file contents');
        $edited = $vehicle->colors()->where('name', 'Urban Mint')->sole();
        $edited->update(['hex' => '#123456', 'secondary_hex' => '#abcdef', 'media_id' => $manualMedia->id]);
        $manualColor = $vehicle->colors()->create(['name' => 'Manual showroom color', 'hex' => '#654321']);
        $white = $vehicle->colors()->where('name', 'Infinity Blanc')->sole()->media;
        $white->update(['alt' => 'Manual white alt']);
        $editedBefore = $edited->fresh()->getAttributes();
        $manualBefore = $manualColor->fresh()->getAttributes();
        $mediaCount = Media::query()->count();
        $fileHashes = collect(Storage::disk('local')->allFiles())->mapWithKeys(
            fn (string $path): array => [$path => hash('sha256', Storage::disk('local')->get($path))]
        )->all();
        $this->travel(1)->days();

        $this->seed(VehicleColorSeeder::class);

        $this->assertDatabaseCount('vehicle_colors', 19);
        $this->assertDatabaseCount('media', $mediaCount);
        $this->assertSame($editedBefore, $edited->fresh()->getAttributes());
        $this->assertSame($manualBefore, $manualColor->fresh()->getAttributes());
        $this->assertSame('Manual white alt', $white->fresh()->alt);
        foreach ($fileHashes as $path => $hash) {
            $this->assertSame($hash, hash('sha256', Storage::disk('local')->get($path)));
        }
    }

    public function test_missing_seed_image_is_restored_without_creating_duplicate_colors_or_media(): void
    {
        $this->seed([VehicleSeeder::class, VehicleColorSeeder::class]);
        $color = VehicleColor::query()->where('name', 'Summer Yellow Body - Jet Black Roof')->sole();
        $media = $color->media;
        Storage::disk('local')->delete($media->path);

        $this->seed(VehicleColorSeeder::class);

        Storage::disk('local')->assertExists($media->path);
        $this->assertSame($media->id, $color->fresh()->media_id);
        $this->assertDatabaseCount('vehicle_colors', 18);
        $this->assertDatabaseCount('media', 18);
    }

    public function test_color_seed_with_no_catalog_vehicles_does_not_create_orphan_records(): void
    {
        $this->seed(VehicleColorSeeder::class);

        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('vehicle_colors', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function catalog(): array
    {
        $catalog = [];
        foreach (['vehicle-colors-vf5-vf7.json', 'vehicle-colors-vf8-vf9.json'] as $filename) {
            $catalog = array_merge($catalog, json_decode(
                File::get(database_path('seeders/content/'.$filename)), true, 512, JSON_THROW_ON_ERROR
            ));
        }

        return $catalog;
    }
}
