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

    private string $disk;
    private string $temp;

    public function __construct(
        private readonly string $tempPath,
        private readonly int $width,
        private readonly int $height,
        private readonly string $filepath,
        private readonly bool $removeTemp = false
    ) {
        $this->disk = config('filesystems.default');
        $this->temp = config('filesystems.default_temp');
    }

    public function handle(): void
    {
        try {
            $fileService = new FileService();
            $tempFile = Storage::disk($this->temp)->get($this->tempPath);
            $imageResized = $fileService->resizeImage($tempFile, $this->width, $this->height);
            $fileService->uploadImage($imageResized, $this->filepath, $this->disk);
            if ($this->removeTemp) {
                Storage::disk($this->temp)->delete($this->temp);
            }
        } catch (Throwable $throwable) {
            $this->activity(log: 'Profile image upload fail', properties: ['message' => $throwable->getMessage()]);
        }
    }
}
