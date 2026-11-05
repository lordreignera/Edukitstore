<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InventoryThresholdReachedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $crossing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $productName = $this->crossing['name'];
        $sku = $this->crossing['sku'];

        return [
            'title' => 'Inventory reached reorder level',
            'body' => "{$productName} ({$sku}) has {$this->crossing['stock_quantity']} units on display; reorder level is {$this->crossing['reorder_level']}.",
            'product_id' => $this->crossing['product_id'],
            'sku' => $sku,
            'stock_quantity' => $this->crossing['stock_quantity'],
            'reorder_level' => $this->crossing['reorder_level'],
            'url' => route('admin.inventory.index', ['q' => $sku]),
        ];
    }
}
