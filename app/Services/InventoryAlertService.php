<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InventoryThresholdReachedNotification;
use Spatie\Permission\Models\Permission;
use Throwable;

class InventoryAlertService
{
    public function notifyThresholdCrossings(array $crossings): void
    {
        if ($crossings === []) {
            return;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'admin']))
            ->get();

        if (Permission::query()->where('name', 'inventory.view')->where('guard_name', 'web')->exists()) {
            $recipients = $recipients->merge(
                User::query()->where('is_active', true)->permission('inventory.view')->get()
            );
        }

        $recipients = $recipients->unique('id');

        foreach ($crossings as $crossing) {
            foreach ($recipients as $recipient) {
                $this->notifyRecipient($recipient, $crossing);
            }
        }
    }

    private function notifyRecipient(User $recipient, array $crossing): void
    {
        try {
            $recipient->notify(new InventoryThresholdReachedNotification($crossing));
            app(PushNotificationService::class)->sendTo($recipient, [
                'title' => 'Low stock: '.$crossing['name'],
                'body' => $crossing['stock_quantity'].' units remain; reorder level is '.$crossing['reorder_level'].'.',
                'url' => route('admin.inventory.index', ['q' => $crossing['sku']]),
                'tag' => 'edukit-low-stock-'.$crossing['product_id'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
