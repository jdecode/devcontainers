<?php

namespace App\Services;

use App\Traits\ActivityLog;
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

    public function uploadImage(Image $image, string $path, ?string $disk = null): void
    {
        if (!$disk) {
            $disk = Storage::getDefaultDriver();
        }
        $imageStream = $image->stream()->__toString();
        Storage::disk($disk)->put($path, $imageStream);
    }
}
