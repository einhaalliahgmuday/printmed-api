<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification
{
    use Queueable;

    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Account Locked Notice')
                    ->view('emails.account_locked', [
                        'name' => $this->name
                    ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            
        ];
    }
}
