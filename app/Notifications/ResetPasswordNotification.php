<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $isNewAccount, $url, $userName;

    public function __construct($isNewAccount, $url, $userName)
    {
        $this->isNewAccount = $isNewAccount;
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
                ->subject('Reset Your Password')
                ->view('emails.reset_password', [
                    'isNewAccount' => $this->isNewAccount,
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
