<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserListRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\ValueObjects\Admin\NotificationVO;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Cookie;

class UserController extends Controller
{
    public function index(UserListRequest $request): View
    {
        $sortBy = $request->validated('sortBy') ?? 'id';
        $orderBy = $request->validated('orderBy') ?? 'asc';
        $perPage = $request->validated('perPage', config('constants.pagination.default_per_page'));

        return view('users.list', [
            'users' => UserResource::collection(
                User::orderBy($sortBy, $orderBy)
                    ->paginate($perPage)
                    ->withQueryString()
            )
        ]);
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::all()->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::all()->pluck('name', 'id'),
        ]);
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $userService = new UserService();
        $userInfo = $request->safe();
        $user = $userService->create($userInfo->toArray());
        $role = $userInfo['role'] ?? null;
        if ($role) {
            $user->assignRole($role);
        }

        event(new Registered($user));

        $redirect = Redirect::route('admin.users.index', $user)->with(
            'notification',
            new NotificationVO(
                NotificationEnum::SUCCESS,
                __('messages.notification.successfully_created'),
                __('messages.user.registered')
            )
        );
        if (in_array(app()->environment(), ['testing', 'local', 'staging'])) {
            $redirect->withCookie(new Cookie('new_user_id', $user->id));
        }

        return $redirect;
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        $update = $request->safe();
        $user->update($update->except('role'));
        $role = $update['role'] ?? [];
        $user->syncRoles($role);

        return Redirect::route('admin.users.index', $user)->with(
            'notification',
            new NotificationVO(
                NotificationEnum::SUCCESS,
                __('messages.notification.successfully_updated'),
                __('messages.user.updated')
            )
        );
    }

    public function verifyEmail(User $user): RedirectResponse
    {
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return Redirect::route('admin.users.index', $user)->with(
            'notification',
            new NotificationVO(
                NotificationEnum::SUCCESS,
                __('messages.notification.successfully_updated'),
                __('messages.user.email_verified')
            )
        );
    }

    /**
     * @throws \App\Exceptions\ForbiddenException
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with(
                'notification',
                new NotificationVO(
                    NotificationEnum::FAIL,
                    __('messages.notification.fail'),
                    __('messages.user.cannot_remove_yourself')
                )
            );
        }
        $user->delete();

        return Redirect::route('admin.users.index', $user)->with(
            'notification',
            new NotificationVO(
                NotificationEnum::SUCCESS,
                __('messages.notification.successfully_deleted'),
                __('messages.user.deleted')
            )
        );
    }
}
