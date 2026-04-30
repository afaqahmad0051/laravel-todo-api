<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\EmailVerificationNotification;

class SendUserVerificationEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        $user->notify(new EmailVerificationNotification($user->verification_code));
    }
}
