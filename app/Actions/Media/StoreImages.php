<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreImages
{
    public function __construct(private StoreImage $storeImage) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, ?string>  $alts
     * @return array<int, Media>
     */
    public function handle(array $files, User $user, array $alts): array
    {
        $stored = [];
        $position = 0;
        try {
            return DB::transaction(function () use ($files, $user, $alts, &$stored, &$position): array {
                foreach ($files as $position => $file) {
                    $alt = $alts[$position] ?? mb_substr(basename($file->getClientOriginalName()), 0, 255);
                    $stored[] = $this->storeImage->handle($file, $user, $alt);
                }

                return $stored;
            });
        } catch (Throwable $exception) {
            foreach ($stored as $media) {
                Storage::disk('local')->delete($media->path);
            }
            if ($exception instanceof ValidationException) {
                throw ValidationException::withMessages(['images.'.$position => $exception->getMessage()]);
            }

            throw $exception;
        }
    }
}
