<?php

namespace Tests\Feature;

use App\Actions\Media\StoreImage;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_batch_upload_preserves_each_alt_and_defaults_to_original_filename(): void
    {
        Storage::fake('local');
        $editor = User::factory()->editor()->create();
        $response = $this->actingAs($editor)->postJson(route('admin.media.store'), [
            'images' => [UploadedFile::fake()->image('vf7-white.png', 100, 80),
                UploadedFile::fake()->image('vf6-blue.jpg', 120, 90)],
            'alts' => ['Ngoại thất VF 7', null],
        ])->assertCreated()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.alt', 'Ngoại thất VF 7')->assertJsonPath('data.1.alt', 'vf6-blue.jpg');
        $this->assertDatabaseCount('media', 2);
        foreach (Media::query()->get() as $item) {
            $this->assertSame($editor->id, $item->user_id);
            $bytes = Storage::disk('local')->get($item->path);
            $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($bytes)[2]);
            $this->assertStringEndsWith('.webp', $item->path);
        }
        $this->assertSame(Media::query()->first()->url(), $response->json('data.0.url'));
    }

    public function test_batch_rejects_unsafe_files_before_storing_any_image(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.media.store'), [
            'images' => [UploadedFile::fake()->image('valid.jpg'),
                UploadedFile::fake()->createWithContent('unsafe.svg', '<svg onload="alert(1)"></svg>')],
        ])->assertUnprocessable()->assertJsonValidationErrors('images.1');
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_conversion_rolls_back_records_and_previously_stored_files(): void
    {
        Storage::fake('local');
        $realAction = new StoreImage;
        $calls = 0;
        $this->mock(StoreImage::class, function (MockInterface $mock) use ($realAction, &$calls): void {
            $mock->shouldReceive('handle')->twice()->andReturnUsing(
                function (UploadedFile $file, User $user, ?string $alt) use ($realAction, &$calls): Media {
                    if (++$calls === 2) {
                        throw ValidationException::withMessages(['image' => 'Không thể đọc ảnh.']);
                    }

                    return $realAction->handle($file, $user, $alt);
                }
            );
        });
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.media.store'), [
            'images' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.png')],
        ])->assertUnprocessable()->assertJsonValidationErrors('images.1');
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_batch_limits_and_alt_length_are_validated(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.media.store'), [
            'images' => array_map(fn ($index) => UploadedFile::fake()->image('image-'.$index.'.png'), range(1, 21)),
        ])->assertUnprocessable()->assertJsonValidationErrors('images');
        $this->postJson(route('admin.media.store'), [
            'images' => [UploadedFile::fake()->image('image.png')], 'alts' => [str_repeat('x', 256)],
        ])->assertUnprocessable()->assertJsonValidationErrors('alts.0');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_upload_rejects_mixed_single_and_batch_payloads(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.media.store'), [
            'image' => UploadedFile::fake()->image('single.png'),
            'images' => [UploadedFile::fake()->image('batch.png')],
        ])->assertUnprocessable()->assertJsonValidationErrors(['image', 'images']);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_library_search_sorting_and_pagination_include_older_images(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $older = Media::factory()->create(['original_name' => 'a-old.png', 'alt' => 'Màu xanh đặc biệt',
            'created_at' => now()->subYear()]);
        Media::factory()->count(100)->create(['original_name' => 'm-car.png']);
        $latest = Media::factory()->create(['original_name' => 'z-new.png', 'created_at' => now()->addMinute()]);
        $url = route('admin.media.index');
        $this->getJson($url)->assertOk()->assertJsonPath('meta.total', 102)->assertJsonCount(24, 'data')
            ->assertJsonPath('data.0.id', $latest->id);
        $this->getJson($url.'?sort=oldest')->assertJsonPath('data.0.id', $older->id);
        $this->getJson($url.'?sort=name_asc')->assertJsonPath('data.0.id', $older->id);
        $this->getJson($url.'?sort=name_desc')->assertJsonPath('data.0.id', $latest->id);
        $this->getJson($url.'?page=5')->assertJsonCount(6, 'data');
        $this->getJson($url.'?q='.urlencode('đặc biệt'))->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $older->id);
        $this->getJson($url.'?id='.$older->id)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', $older->url());
        $this->getJson($url.'?sort=invalid')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_sales_cannot_browse_or_upload_to_library(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->sales()->create())->getJson(route('admin.media.index'))->assertForbidden();
        $this->postJson(route('admin.media.store'), [
            'images' => [UploadedFile::fake()->image('image.png')],
        ])->assertForbidden();
        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_controls_have_unique_ids_and_escape_image_names(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Kiểm tra giao diện']));
        $cover = Media::factory()->create(['original_name' => 'vf7-white.webp', 'alt' => 'Ngoại thất VF 7']);
        $vehicle = Vehicle::factory()->create(['media_id' => $cover->id]);
        $vehicle->colors()->create(['name' => 'Infinity Blanc', 'hex' => '#ffffff', 'media_id' => $cover->id]);
        $html = $this->get(route('admin.vehicles.edit', $vehicle))->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $ids = array_map(fn ($node) => $node->getAttribute('id'), iterator_to_array(
            (new \DOMXPath($document))->query('//*[@id]')
        ));
        $this->assertCount(count(array_unique($ids)), $ids);
        Media::factory()->create(['original_name' => '<script>alert(1)</script>.png']);
        $this->get(route('admin.media.index'))->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_vehicle_form_keeps_older_selected_images_and_submitted_color_values(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = Media::factory()->create(['original_name' => 'selected-old.png']);
        Media::factory()->count(101)->create();
        $vehicle = Vehicle::factory()->create(['media_id' => $media->id]);
        $this->get(route('admin.vehicles.edit', $vehicle))->assertOk()
            ->assertSee('selected-old.png')->assertSee('data-color-swatch', false)
            ->assertSee('data-media-open', false)->assertSee('admin-media-dialog');
        $this->put(route('admin.vehicles.update', $vehicle), [
            'name' => $vehicle->name, 'slug' => $vehicle->slug, 'is_active' => true,
            'media_id' => $media->id,
            'colors' => [['name' => 'Trắng', 'hex' => '#ffffff', 'secondary_hex' => null, 'media_id' => $media->id]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vehicle_colors', ['vehicle_id' => $vehicle->id,
            'hex' => '#ffffff', 'secondary_hex' => null, 'media_id' => $media->id]);
        $this->get(route('admin.media.index', ['columns' => 6]))->assertOk()
            ->assertSee('data-columns="6"', false)->assertSee('multiple', false)->assertSee('data-upload-queue', false);
    }
}
