<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Traits\ActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;
use Intervention\Image\Facades\Image as FacadeImage;

class FileService
{
    use ActivityLog;

    public function resizeImage(string $image, int $width, int $height): Image
    {
        $image = FacadeImage::make($image);
        if ($image->width() > $width || $image->height() > $height) {
            return FacadeImage::make($image)->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        return $image;
    }

    public function randomFilename(string $ext = ''): string
    {
        $filename = uniqid(more_entropy: true);
        if ($ext) {
            $filename .= '.' . $ext;
        }
        return $filename;
    }

    /**
     * @throws ForbiddenException
     */
    public function tempStore(UploadedFile $file, string $filename): string
    {
        $path = config('filesystems.temp_path');
        $stored = $file->storeAs($path, $filename, config('filesystems.default_temp'));
        if (!$stored) {
            throw new ForbiddenException('Cannot store temp file on this server');
        }
        return $stored;
    }

    public function uploadImage(Image $image, string $path, ?string $disk = null): void
    {
        if (!$disk) {
            $disk = Storage::getDefaultDriver();
        }
        $imageStream = $image->stream()->__toString();
        Storage::disk($disk)->put($path, $imageStream);
    }
}
