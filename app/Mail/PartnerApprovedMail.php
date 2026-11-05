<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $partnerName, public string $partnerType) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: "Your EduKit {$this->partnerType} account is approved",
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(view: 'emails.partners.approved');
    }
}
