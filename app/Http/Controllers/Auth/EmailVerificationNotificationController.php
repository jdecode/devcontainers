<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationNotificationController extends Controller
{
    use HttpResponse;

    public function store(
        Request $request,
        ?User $user
    ): JsonResponse {
        $user = $user ?? $request->user();
        if ($user->hasVerifiedEmail()) {
            return $this->response(
                [],
                'User is already verified',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user->sendEmailVerificationNotification();
        return $this->response(
            ['status' => 'verification-link-sent'],
            'Verification link sent'
        );
    }
}
