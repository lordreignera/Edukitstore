<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Persist a product announcement for an explicitly opted-in customer account. */
class ProductAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'product_available',
            'title' => 'New product available',
            'body' => "{$this->product->name} is now available in the EduKit store.",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'url' => route('website.products.show', $this->product),
        ];
    }
}
