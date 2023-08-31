<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\ProfileImageUploadNotification;
use Illuminate\Auth\Events\Verified;

class NoProfileImageListener
{
    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $event->user->notify(new ProfileImageUploadNotification());
        }
    }
}
