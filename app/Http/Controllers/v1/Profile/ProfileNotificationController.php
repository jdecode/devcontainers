<?php

namespace App\Http\Controllers\v1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UserNotificationRequest;
use App\Http\Requests\UpdateProfileNotificationRequest;
use App\Traits\ActivityLog;
use App\Traits\HttpResponse;
use Auth;
use Str;
use Symfony\Component\HttpFoundation\Response;

class ProfileNotificationController extends Controller
{
    use HttpResponse;
    use ActivityLog;

    public function show(UserNotificationRequest $request)
    {
        $user = Auth::user();
        $notificationsQuery = $user->notifications()->orderByDesc('created_at');
        $status = $request->validated('status');
        $perPage = $request->validated('perPage', config('constants.pagination.default_per_page'));
        if ($status) {
            $method = $status === 'read' ? 'whereNotNull' : 'whereNull';
            $notificationsQuery->$method('read_at');
        }
        $notifications = $notificationsQuery
            ->paginate($perPage, ['id', 'type', 'data', 'read_at', 'created_at']);
        $notifications->map(fn ($item) => $item->type = Str::afterLast($item->type, '\\'));
        $unreadCount = $notifications->total();
        if ($status !== 'unread') {
            $unreadCount = $user->notifications->whereNull('read_at')->count();
        }
        $notifications = $notifications->toArray();
        $notifications['unread_count'] = $unreadCount;
        return $notifications;
    }

    public function update(UpdateProfileNotificationRequest $request)
    {
        $user = Auth::user();
        $notificationId = $request->validated('id');
        $notificationsQuery = $user->notifications()->whereNull('read_at');
        if ($notificationId) {
            $notificationsQuery->whereKey($notificationId);
        }
        $notifications = $notificationsQuery->get();
        if ($notifications->isEmpty()) {
            return $this->response(
                [],
                __('messages.profile.notification.update_fail'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $notifications->each(fn ($notification) => $notification->markAsRead());
        return $this->response(
            ['ids' => $notifications->pluck('id')],
            __('messages.profile.notification.update_success')
        );
    }
}
