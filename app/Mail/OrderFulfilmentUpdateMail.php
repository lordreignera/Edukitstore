<?php

namespace App\Mail;

use App\Models\ShoppingList;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderFulfilmentUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShoppingList $order,
        public string $title,
        public string $bodyText,
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: "{$this->title} — {$this->order->reference}",
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(view: 'emails.orders.fulfilment-update');
    }
}
