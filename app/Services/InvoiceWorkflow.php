<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceWorkflow
{
    public function updateFromAdmin(ShoppingList $invoice, array $data, int $adminId): ShoppingList
    {
        if (($data['status'] ?? null) === ShoppingList::STATUS_FULFILLED && ! $invoice->delivery_confirmed_at) {
            throw ValidationException::withMessages([
                'status' => 'A transaction can only be fulfilled after the assigned driver confirms delivery.',
            ]);
        }

        $assignedDriverId = $data['assigned_driver_id'] ?? $invoice->assigned_driver_id;

        if (($data['status'] ?? null) === ShoppingList::STATUS_QUOTED && ! $assignedDriverId) {
            throw ValidationException::withMessages([
                'assigned_driver_id' => 'Assign an approved driver before releasing the invoice.',
            ]);
        }

        if ($assignedDriverId) {
            $driverIsAssignable = Driver::whereKey($assignedDriverId)
                ->where('is_approved', true)
                ->where('is_available', true)
                ->exists();

            if (! $driverIsAssignable) {
                throw ValidationException::withMessages([
                    'assigned_driver_id' => 'Choose an approved and available driver.',
                ]);
            }
        }

        if ($invoice->source === ShoppingList::SOURCE_CART) {
            $deliveryFee = $data['delivery_fee'] ?? null;
            $data['estimated_total'] = $deliveryFee === null
                ? null
                : $invoice->items_subtotal + (int) $deliveryFee;
        }

        return DB::transaction(function () use ($invoice, $data, $adminId): ShoppingList {
            $invoice->update($data + [
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
            ]);

            return $invoice->refresh();
        });
    }
}
