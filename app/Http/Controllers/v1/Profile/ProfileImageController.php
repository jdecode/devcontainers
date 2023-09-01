<?php

namespace App\Http\Controllers\v1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileImageUploadRequest;
use App\Jobs\ProfileImageJob;
use App\Services\UserService;
use App\Traits\ActivityLog;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProfileImageController extends Controller
{
    use HttpResponse;
    use ActivityLog;

    public function store(ProfileImageUploadRequest $request): JsonResponse
    {
        try {
            $image = $request->file('file');
            $user = Auth::user();
            dispatch(new ProfileImageJob($image, $user))->onQueue('default');

            return $this->response(
                [],
                __('messages.profile.image.accepted'),
                Response::HTTP_ACCEPTED
            );
        } catch (Throwable $throwable) {
            $user = Auth::user();
            $this->activity($throwable->getMessage(), $user, $user, ['trace' => $throwable->getTraceAsString()]);

            return $this->response(
                [],
                __('messages.profile.image.upload_fail'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(): JsonResponse
    {
        try {
            $user = Auth::user();
            $service = new UserService();
            $service->deleteUserImage($user);

            return $this->response(message: __('messages.profile.image.delete_success'));
        } catch (Throwable $throwable) {
            $user = Auth::user();
            $this->activity('Delete profile image failed', $user, $user, ['message' => $throwable->getMessage()]);

            return $this->response(
                [],
                __('messages.profile.image.delete_fail'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function status(): JsonResponse
    {
        return $this->response(['status' => Auth::user()->image_upload_status]);
    }
}
