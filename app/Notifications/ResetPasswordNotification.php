<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $url, $userName;

    public function __construct(string $url, string $userName)
    {
        $this->url = $url;
        $this->userName = $userName;
    }


    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                ->view('emails.reset_password', [
                    'url' => $this->url,
                    'name' => $this->userName
                ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            
        ];
    }
}
