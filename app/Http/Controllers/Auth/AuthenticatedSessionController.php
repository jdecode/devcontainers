<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\UserService;
use App\Traits\ActivityLog;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    use ActivityLog;
    use HttpResponse;

    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $user = Auth::user();
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();

            return $this->response(
                message: __('auth.email_verification'),
                httpCode: Response::HTTP_FORBIDDEN
            );
        }
        $token = $user->createToken(request()->userAgent())->plainTextToken;
        $this->activity('Log in', $user, $user);

        return $this->response(
            array_merge(Auth::user()->toArray(), ['token' => $token]),
            __('messages.user.logged_in')
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $service = new UserService();
        $service->expireTokens($user);
        $this->activity('Log out', $user, $user);

        return $this->response(['token' => ''], __('messages.user.logged_out'));
    }
}
