<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreImage
{
    public function handle(UploadedFile $file, User $user, ?string $alt): Media
    {
        $info = getimagesize($file->getRealPath());
        if (! $info || $info[0] * $info[1] > 20000000) {
            throw ValidationException::withMessages(['image' => 'Ảnh quá lớn hoặc không hợp lệ.']);
        }
        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'Không thể đọc ảnh.']);
        }
        $width = min(1920, $info[0]);
        $height = max(1, (int) round($info[1] * $width / $info[0]));
        $resized = imagescale($image, $width, $height);
        imagepalettetotruecolor($resized);
        imagesavealpha($resized, true);
        ob_start();
        imagewebp($resized, null, 82);
        $bytes = ob_get_clean();
        $path = 'images/'.Str::uuid().'.webp';
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw new \RuntimeException('Không thể lưu ảnh. Vui lòng thử lại.');
        }
        try {
            return Media::query()->create([
                'user_id' => $user->id, 'path' => $path, 'original_name' => basename($file->getClientOriginalName()),
                'alt' => $alt, 'width' => $width, 'height' => $height, 'size' => strlen($bytes),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
