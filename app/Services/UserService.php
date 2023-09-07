<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Jobs\RemoveFileJob;
use App\Jobs\VerifyEmailJob;
use App\Models\User;
use App\Traits\ActivityLog;
use Config;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        $type = $thumbnail ? 'thumbnail_params' : 'image_params';
        $path = config("constants.user.profile_image.$type.path");
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

        $user->update([
            'image_filename' => null,
            'image_upload_status' => null
        ]);
    }

    public function getAllUsers(): AnonymousResourceCollection
    {
        return UserResource::collection(User::all());
    }
}
