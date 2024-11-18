<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientIdCard extends Mailable
{
    use Queueable, SerializesModels;
    public $photo, $patientFirstName;

    public function __construct($photo, $patientFirstName)
    {
        $this->photo = $photo;
        $this->patientFirstName = $patientFirstName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Digital Identification Card',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.patient_id_card_email',
            with: [
                'patient_first_name' => $this->patientFirstName 
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->photo, 'patient_id_card.jpeg')->withMime('image/jpeg'),
        ];
    }
}
