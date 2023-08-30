<?php

namespace App\Jobs;

use App\Enums\ProfileImageUploadStatusEnum;
use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Services\FileService;
use App\Traits\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;
use Throwable;

class ProfileImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use ActivityLog;

    private string $disk;
    private string $tempDisk;
    private string $tempFile;
    private FileService $fileService;
    private string $filename;
    private array $imageParams;
    private array $thumbnailParams;
    private ?string $oldFilename;

    /**
     * @throws ForbiddenException
     */
    public function __construct(
        UploadedFile $image,
        private readonly User $user,
    ) {
        $this->disk = config('filesystems.default');
        $this->tempDisk = config('filesystems.default_temp');
        $this->fileService = new FileService();
        $ext = $image->getClientOriginalExtension();
        $this->filename = $this->fileService->randomFilename($ext);
        $this->imageParams = config('constants.user.profile_image.image_params');
        $this->thumbnailParams = config('constants.user.profile_image.thumbnail_params');
        $this->tempFile = $this->fileService->tempStore($image, $this->filename);
        if (!$this->tempFile) {
            throw new ForbiddenException('Cannot store temp user image on this server');
        }
        $this->oldFilename = $this->user->image_filename;
        $this->user->update([
            'image_filename' => null,
            'image_upload_status' => ProfileImageUploadStatusEnum::PROCESSING
        ]);
    }

    public function handle(): void
    {
        try {
            $this->removeOld();
            $tempFile = Storage::disk($this->tempDisk)->get($this->tempFile);
            $this->processOriginalImage($tempFile);
            $this->processThumbnail($tempFile);
            $this->user->update([
                'image_filename' => $this->filename,
                'image_upload_status' => ProfileImageUploadStatusEnum::UPLOADED
            ]);
            $this->cleanUp();
            return;
        } catch (UnableToReadFile $exception) {
            $this->activity(
                log: $exception->getMessage(),
                properties: ['trace' => $exception->getTraceAsString()]
            );
            $this->user->update(['image_upload_status' => ProfileImageUploadStatusEnum::FAILED]);
        } catch (Throwable $throwable) {
            $this->activity(
                log: $throwable->getMessage(),
                properties: ['trace' => $throwable->getTraceAsString()]
            );
            $this->user->update(['image_upload_status' => ProfileImageUploadStatusEnum::FAILED]);
            $this->cleanUp();
        }
    }

    private function processOriginalImage(string $tempFile): void
    {
        $imageResized = $this->fileService->resizeImage(
            $tempFile,
            $this->imageParams['width_px'],
            $this->imageParams['height_px']
        );
        $this->fileService->uploadImage($imageResized, $this->imageParams['path'] . $this->filename, $this->disk);
    }

    private function processThumbnail(string $tempFile): void
    {
        $thumbnail = $this->fileService->resizeImage(
            $tempFile,
            $this->thumbnailParams['width_px'],
            $this->thumbnailParams['height_px']
        );
        $this->fileService->uploadImage($thumbnail, $this->thumbnailParams['path'] . $this->filename, $this->disk);
    }

    private function removeOld(): void
    {
        if (!$this->oldFilename) {
            return;
        }

        $image = $this->imageParams['path'] . $this->oldFilename;
        if (Storage::disk($this->disk)->exists($image)) {
            Storage::disk($this->disk)->delete($image);
        }

        $thumbnail = $this->thumbnailParams['path'] . $this->oldFilename;
        if (Storage::disk($this->disk)->exists($thumbnail)) {
            Storage::disk($this->disk)->delete($thumbnail);
        }
    }

    private function cleanUp(): void
    {
        if (Storage::disk($this->tempDisk)->exists($this->tempFile)) {
            Storage::disk($this->tempDisk)->delete($this->tempFile);
        }
    }
}
