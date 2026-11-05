<?php

namespace App\Notifications;

use App\Models\ShoppingList;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ShoppingList $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New order received',
            'body' => "Order {$this->order->reference} is ready for review.",
            'reference' => $this->order->reference,
            'url' => route('admin.invoices.show', $this->order),
        ];
    }
}
