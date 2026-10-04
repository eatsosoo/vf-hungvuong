<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VehicleColorSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [];
        foreach (['vehicle-colors-vf5-vf7.json', 'vehicle-colors-vf8-vf9.json'] as $filename) {
            $catalog = array_merge($catalog, json_decode(
                File::get(database_path('seeders/content/'.$filename)), true, 512, JSON_THROW_ON_ERROR,
            ));
        }

        $vehicles = Vehicle::query()->whereIn('slug', array_keys($catalog))
            ->with(['media', 'colors.media'])->get()->keyBy('slug');
        $count = DB::transaction(function () use ($catalog, $vehicles): int {
            $count = 0;

            foreach ($catalog as $slug => $colors) {
                $vehicle = $vehicles->get($slug);
                if ($vehicle === null) {
                    $this->command?->warn('Chưa có dòng xe '.$slug.'. Hãy chạy VehicleSeeder trước.');

                    continue;
                }

                foreach ($colors as $entry) {
                    $path = 'images/vehicle-colors/'.pathinfo($entry['image'], PATHINFO_FILENAME).'.webp';
                    if ($entry['name'] === 'Infinity Blanc' && $vehicle->media?->path === 'images/'.$slug.'.webp') {
                        $path = $vehicle->media->path;
                    }

                    $media = $this->seedImage($entry['image'], $path, $vehicle->name.' màu '.$entry['name']);
                    $color = $vehicle->colors()->firstOrNew(['name' => $entry['name']]);
                    $defaults = [
                        'hex' => $entry['hex'],
                        'secondary_hex' => $entry['secondary_hex'] ?? null,
                        'media_id' => $media->id,
                    ];
                    foreach ($defaults as $field => $value) {
                        if (! $color->exists || $color->{$field} === null || $color->{$field} === '') {
                            $color->{$field} = $value;
                        }
                    }
                    if ($color->isDirty()) {
                        $color->save();
                    }
                    $count++;
                }
            }

            return $count;
        });

        $this->command?->info('Đã bổ sung danh mục '.$count.' màu xe và ảnh nền trong suốt từ VinFast.');
    }

    private function seedImage(string $filename, string $path, string $alt): Media
    {
        $source = imagecreatefromstring(File::get(public_path('assets/vinfast/'.$filename)));
        if ($source === false) {
            throw new RuntimeException('Không thể đọc ảnh màu xe: '.$filename);
        }

        imagepalettetotruecolor($source);
        $width = min(1920, imagesx($source));
        $height = max(1, (int) round(imagesy($source) * $width / imagesx($source)));
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        ob_start();
        $encoded = imagewebp($image, null, 88);
        $bytes = ob_get_clean();
        if (! $encoded || ! is_string($bytes)) {
            throw new RuntimeException('Không thể chuyển ảnh màu xe sang WebP: '.$filename);
        }

        $disk = Storage::disk('local');
        $changed = ! $disk->exists($path) || hash('sha256', $disk->get($path)) !== hash('sha256', $bytes);
        if ($changed && ! $disk->put($path, $bytes)) {
            throw new RuntimeException('Không thể lưu ảnh màu xe: '.$path);
        }

        $media = Media::query()->firstOrNew(['path' => $path]);
        $media->fill([
            'original_name' => $filename,
            'alt' => $media->alt ?: $alt,
            'width' => $width,
            'height' => $height,
            'size' => strlen($bytes),
        ]);
        if ($changed || $media->isDirty()) {
            $media->setUpdatedAt(now());
            $media->save();
        }

        return $media;
    }
}
