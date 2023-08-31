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
            'mail' => now()->addHour()
        ];
    }

    public function via(): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Kindly request')
            ->greeting("Hello $notifiable->full_name!")
            ->line('You haven’t updated your profile picture, login and let other users see what you look like')
            ->line('Thank you for using our application!');
    }

    public function toArray(): array
    {
        return [
            'title' => 'Kindly request',
            'message' => 'You haven’t updated your profile picture, login and let other users see what you look like'
        ];
    }

    public function shouldSend(object $notifiable): bool
    {
        return !(bool) $notifiable->image_filename;
    }
}
