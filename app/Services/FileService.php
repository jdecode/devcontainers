<?php

namespace App\Services;

use App\Traits\ActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;
use Intervention\Image\Facades\Image as FacadeImage;

class FileService
{
    use ActivityLog;

    public function resizeImage(UploadedFile $image, int $width, int $height): Image
    {
        $image = FacadeImage::make($image->getContent());
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

    public function uploadImage(Image $image, string $path): void
    {
        $imageStream = $image->stream()->__toString();
        Storage::put($path, $imageStream);
    }
}
