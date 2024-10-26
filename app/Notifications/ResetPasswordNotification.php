<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $isNewAccount, $token, $email;

    public function __construct($isNewAccount, $token, $email)
    {
        $this->isNewAccount = $isNewAccount;
        $this->token = $token;
        $this->email = $email;
    }


    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("http://127.0.0.1/reset-password?token={$this->token}&email={$this->email}");

        return (new MailMessage)
                ->subject('Reset Your Password')
                ->view('emails.reset_password', [
                    'isNewAccount' => $this->isNewAccount,
                    'url' => $url
                ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            
        ];
    }
}
