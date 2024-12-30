<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Prescription extends Mailable
{
    use Queueable, SerializesModels;
    public $prescriptionImages, $patientFirstName;

    public function __construct($prescriptionImages, $patientFirstName)
    {
        $this->prescriptionImages = $prescriptionImages;
        $this->patientFirstName = $patientFirstName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Prescription from Latest Consultation at Carmona Hospital and Medical Center',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prescription_email',
            with: [
                'patient_first_name' => $this->patientFirstName 
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach($this->prescriptionImages as $index => $image) {
            $count = $index + 1;
            $attachments[] = Attachment::fromData(fn () => $image, "prescription_{$count}.jpeg")->withMime('image/jpeg');
        }

        return $attachments;
    }
}
