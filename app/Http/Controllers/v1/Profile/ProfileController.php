<?php

namespace App\Http\Controllers\v1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use HttpResponse;

    public function show(): JsonResponse
    {
        $user = Auth::user();

        return $this->resourceResponse(new UserResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $message = __('messages.profile.updated');

        if ($user->isDirty('email')) {
            $service = new UserService();
            $service->emailReVerification($user);
            $service->expireTokens($user);
            $message = __('messages.profile.updated_with_email');
        }
        $user->save();

        return $this->resourceResponse(
            new UserResource($request->user()),
            $message
        );
    }

    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        $user->delete();

        return $this->response([], __('messages.profile.deleted'));
    }
}
