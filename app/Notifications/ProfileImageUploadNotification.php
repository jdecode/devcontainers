<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileImageUploadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function withDelay(): array
    {
        return [
            'database' => now(),
            'mail' => now()->addMinutes(10)
        ];
    }

    public function via(): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('messages.profile.image.notification.not_set_yet_title'))
            ->greeting("Hello $notifiable->full_name!")
            ->line(__('messages.profile.image.notification.not_set_yet'))
            ->line(__('messages.profile.image.notification.thank_you'));
    }

    public function toArray(): array
    {
        return [
            'title' => __('messages.profile.image.notification.not_set_yet_title'),
            'message' => __('messages.profile.image.notification.not_set_yet')
        ];
    }

    public function shouldSend(object $notifiable): bool
    {
        return !(bool) $notifiable->image_filename;
    }
}
