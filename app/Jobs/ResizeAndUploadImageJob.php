<?php

namespace App\Jobs;

use App\Services\FileService;
use App\Traits\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ResizeAndUploadImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use ActivityLog;

    public function __construct(
        private readonly string $imagePath,
        private readonly int $width,
        private readonly int $height,
        private readonly string $filepath,
        private readonly string $disk,
    ) {
    }

    public function handle(): void
    {
        try {
            $this->activity(Storage::getDefaultDriver());
            $fileService = new FileService();
            $image = Storage::disk('local')->get($this->imagePath);
            $imageResized = $fileService->resizeImage($image, $this->width, $this->height);
            $fileService->uploadImage($imageResized, $this->filepath, $this->disk);
            Storage::disk('local')->delete($this->imagePath);
        } catch (Throwable $throwable) {
            $this->activity(log: 'Profile image upload fail', properties: ['message' => $throwable->getMessage()]);
        }
    }
}
