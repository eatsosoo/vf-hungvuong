<?php

namespace Tests\Feature;

use App\Actions\Media\StoreImage;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MediaLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_single_upload_resizes_to_max_width_and_reports_real_webp_metadata(): void
    {
        $user = User::factory()->editor()->create();
        $response = $this->actingAs($user)->postJson(route('admin.media.store'), [
            'image' => UploadedFile::fake()->image('large-car.png', 2400, 1200),
        ])->assertCreated()->assertJsonPath('alt', 'large-car.png')
            ->assertJsonPath('original_name', 'large-car.png')
            ->assertJsonPath('width', 1920)->assertJsonPath('height', 960);

        $media = Media::query()->sole();
        $bytes = Storage::disk('local')->get($media->path);
        $dimensions = getimagesizefromstring($bytes);
        $this->assertSame([1920, 960, IMAGETYPE_WEBP], array_slice($dimensions, 0, 3));
        $this->assertSame(strlen($bytes), $media->size);
        $this->assertSame($media->size, $response->json('size'));
        $this->assertSame($user->id, $media->user_id);
        $this->assertSame($media->url(), $response->json('url'));
    }

    public function test_small_upload_keeps_dimensions_and_explicit_alt(): void
    {
        $this->actingAs(User::factory()->manager()->create())->postJson(route('admin.media.store'), [
            'image' => UploadedFile::fake()->image('small.png', 80, 60), 'alt' => 'Ảnh ngoại thất',
        ])->assertCreated()->assertJsonPath('width', 80)->assertJsonPath('height', 60)
            ->assertJsonPath('alt', 'Ảnh ngoại thất');
    }

    #[DataProvider('invalidUploads')]
    public function test_invalid_single_upload_is_rejected_without_side_effects(string $case): void
    {
        $file = match ($case) {
            'too large' => UploadedFile::fake()->image('oversized.png')->size(5121),
            'too wide' => UploadedFile::fake()->image('wide.png', 6001, 1),
            'disguised text' => UploadedFile::fake()->createWithContent('fake.png', 'not an image'),
        };
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.media.store'), [
            'image' => $file,
        ])->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @return array<string, array{string}> */
    public static function invalidUploads(): array
    {
        return array_combine(
            ['too large', 'too wide', 'disguised text'],
            [['too large'], ['too wide'], ['disguised text']]
        );
    }

    public function test_storage_failure_does_not_create_media_record(): void
    {
        $user = User::factory()->editor()->create();
        $failedDisk = Mockery::mock(FilesystemAdapter::class);
        $failedDisk->shouldReceive('put')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($failedDisk);

        try {
            app(StoreImage::class)->handle(UploadedFile::fake()->image('car.png'), $user, null);
            $this->fail('A storage failure must be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Không thể lưu ảnh. Vui lòng thử lại.', $exception->getMessage());
        }

        $this->assertDatabaseCount('media', 0);
    }

    public function test_database_failure_after_file_conversion_removes_orphan_file(): void
    {
        $deletedUploader = User::factory()->editor()->create();
        $deletedUploader->delete();

        try {
            app(StoreImage::class)->handle(UploadedFile::fake()->image('car.png'), $deletedUploader, null);
            $this->fail('A missing uploader must fail the media foreign key constraint.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('FOREIGN KEY constraint failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_public_media_returns_saved_file_with_cache_headers_and_missing_files_return_404(): void
    {
        $media = Media::factory()->create();
        $file = UploadedFile::fake()->image('car.webp');
        $bytes = file_get_contents($file->getRealPath());
        Storage::disk('local')->put($media->path, $bytes);
        $response = $this->get($media->url())->assertOk()->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('Cache-Control', 'max-age=86400, public');
        $this->assertSame(
            Storage::disk('local')->path($media->path), $response->baseResponse->getFile()->getPathname()
        );

        Storage::disk('local')->delete($media->path);
        $this->get($media->url())->assertNotFound();
        $this->get(route('media.show', ['media' => $media->id + 1]))->assertNotFound();
    }

    public function test_alt_update_keeps_file_and_owner_and_rejects_oversized_alt(): void
    {
        $this->freezeTime();
        $owner = User::factory()->editor()->create();
        $media = Media::factory()->create(['user_id' => $owner->id, 'alt' => 'Alt cũ']);
        $bytes = 'unchanged file';
        Storage::disk('local')->put($media->path, $bytes);
        $oldUrl = $media->url();
        $this->travel(1)->seconds();
        $this->actingAs(User::factory()->manager()->create())->put(route('admin.media.update', $media), [
            'alt' => 'Mô tả cập nhật', 'path' => 'malicious.webp', 'user_id' => 999,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $updated = $media->fresh();
        $this->assertSame('Mô tả cập nhật', $updated->alt);
        $this->assertSame($owner->id, $updated->user_id);
        $this->assertSame($media->path, $updated->path);
        $this->assertSame($bytes, Storage::disk('local')->get($media->path));
        $this->assertNotSame($oldUrl, $updated->url());
        $this->putJson(route('admin.media.update', $media), ['alt' => str_repeat('a', 256)])
            ->assertUnprocessable()->assertJsonValidationErrors('alt');
        $this->assertSame('Mô tả cập nhật', $media->fresh()->alt);
        $this->put(route('admin.media.update', $media), ['alt' => null])->assertSessionHasNoErrors();
        $this->assertNull($media->fresh()->alt);
    }

    public function test_sales_and_guests_cannot_update_media_metadata(): void
    {
        $media = Media::factory()->create(['alt' => 'Alt gốc']);
        $this->putJson(route('admin.media.update', $media), ['alt' => 'Changed'])->assertUnauthorized();
        $this->actingAs(User::factory()->sales()->create())->putJson(route('admin.media.update', $media), [
            'alt' => 'Changed',
        ])->assertForbidden();

        $this->assertSame('Alt gốc', $media->fresh()->alt);
    }
}
