<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Jobs\RemoveFileJob;
use App\Jobs\ResizeAndUploadImageJob;
use App\Jobs\VerifyEmailJob;
use App\Models\User;
use App\Traits\ActivityLog;
use Config;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    use ActivityLog;

    public function create(array $userInfo): User
    {
        return User::create(
            [
                'first_name' => $userInfo['first_name'],
                'last_name' => $userInfo['last_name'] ?? null,
                'email' => $userInfo['email'],
                'password' => Hash::make($userInfo['password']),
            ]
        );
    }

    public function register(array $userInfo): array
    {
        $user = $this->create($userInfo);
        $user->assignRole(Config::get('constants.roles.user'));
        dispatch(new VerifyEmailJob($user))->onQueue('default');
        return $user->toArray();
    }

    public function emailReVerification(User $user): void
    {
        $user->email_verified_at = null;
        $user->sendEmailVerificationNotification();
    }

    public function expireTokens(User $user): void
    {
        $user->tokens()->update(['expires_at' => now()]);
    }

    public function getUserImage(User $user, bool $thumbnail = false): ?string
    {
        $pathType = $thumbnail ? 'thumbnail_path' : 'path';
        $path = config("constants.user.profile_image.$pathType");
        $filename = $user->image_filename;
        if (!$filename) {
            return null;
        }
        if (Storage::providesTemporaryUrls()) {
            return Storage::temporaryUrl($path . $filename, now()->addHour());
        }
        return Storage::url($path . $filename);
    }

    public function deleteUserImage(User $user): void
    {
        $path = config('constants.user.profile_image.path');
        $thumbnailPath = config('constants.user.profile_image.thumbnail_path');

        if ($user->image_filename) {
            dispatch(new RemoveFileJob($path . '/' . $user->image_filename))->onQueue('default');
            dispatch(new RemoveFileJob($thumbnailPath . '/' . $user->image_filename))->onQueue('default');
        }

        $user->update(['image_filename' => null]);
    }

    /**
     * @throws ForbiddenException
     */
    public function upsertUserImage(User $user, UploadedFile $image): array
    {
        $service = new FileService();
        $ext = $image->getClientOriginalExtension();
        $filename = $service->randomFilename($ext);
        $path = config('constants.user.profile_image.path');
        $width = config('constants.user.profile_image.image_width_px');
        $height = config('constants.user.profile_image.image_height_px');
        $temp = $service->tempStore($image, $filename);
        if (!$temp) {
            throw new ForbiddenException('Cannot store temp user image on this server');
        }
        dispatch(new ResizeAndUploadImageJob($temp, $width, $height, $path . $filename))
            ->onQueue('default');

        $thumbnailPath = config('constants.user.profile_image.thumbnail_path');
        $thumbnailWidth = config('constants.user.profile_image.thumbnail_width_px');
        $thumbnailHeight = config('constants.user.profile_image.thumbnail_height_px');
        dispatch(new ResizeAndUploadImageJob(
            $temp,
            $thumbnailWidth,
            $thumbnailHeight,
            $thumbnailPath . $filename,
            true
        ))->onQueue('default');

        if ($user->image_filename) {
            dispatch(new RemoveFileJob($path . $user->image_filename))->onQueue('default');
            dispatch(new RemoveFileJob($thumbnailPath . $user->image_filename))->onQueue('default');
        }

        $user->update(['image_filename' => $filename]);

        if (!Storage::providesTemporaryUrls()) {
            return [
                'image' => Storage::url($path . $filename),
                'thumbnail' => Storage::url($thumbnailPath . $filename)
            ];
        }
        return [
            'image' => Storage::temporaryUrl($path . $filename, now()->addHour()),
            'thumbnail' => Storage::temporaryUrl($thumbnailPath . $filename, now()->addHour())
        ];
    }
}
