<?php

namespace App\Mail;

use App\Models\ShoppingList;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPaidThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ShoppingList $order) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: "Thank you for your EduKit payment — {$this->order->reference}",
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(view: 'emails.orders.paid');
    }
}
