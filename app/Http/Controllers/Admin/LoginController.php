<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Traits\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    use ActivityLog;

    public function index(): View
    {
        return view('login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = Auth::user();
        $hasPermissions = $user->hasPermissionTo('view dashboard');
        $hasRole = $user->hasRole(config('constants.roles.super_admin'));
        if (!$hasPermissions && !$hasRole) {
            Auth::logout();

            return redirect()
                ->back()
                ->withErrors(['email' => __('messages.error.no_rights_to_dashboard')]);
        }
        $request->session()->regenerate();
        $this->activity('Dashboard Log in', $user, $user);
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        $this->activity('Dashboard Log out', $user, $user);
        return redirect(route('admin.login'));
    }
}
