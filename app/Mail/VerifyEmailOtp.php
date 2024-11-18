<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailOtp extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm Your Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify_otp',
            with: [
                'isVerifyEmail' => true,
                'code' => $this->code,
            ],
        );
    }
}
