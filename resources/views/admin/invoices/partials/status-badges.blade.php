@php
    $invoiceTone = match ($invoice->status) {
        \App\Models\ShoppingList::STATUS_PENDING => 'bg-amber-50 text-amber-700',
        \App\Models\ShoppingList::STATUS_REVIEWING => 'bg-blue-50 text-blue-700',
        \App\Models\ShoppingList::STATUS_QUOTED => 'bg-orange-50 text-orange-700',
        \App\Models\ShoppingList::STATUS_FULFILLED => 'bg-orange-50 text-orange-700',
        \App\Models\ShoppingList::STATUS_REJECTED, \App\Models\ShoppingList::STATUS_CANCELLED => 'bg-red-50 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
    $paymentTone = match ($invoice->payment_status) {
        \App\Models\ShoppingList::PAYMENT_PAID => 'bg-orange-50 text-orange-700',
        \App\Models\ShoppingList::PAYMENT_PENDING => 'bg-blue-50 text-blue-700',
        \App\Models\ShoppingList::PAYMENT_FAILED, \App\Models\ShoppingList::PAYMENT_REFUNDED => 'bg-red-50 text-red-700',
        default => 'bg-amber-50 text-amber-700',
    };
    $deliveryTone = match ($invoice->deliveryStatus()) {
        'delivered' => 'bg-orange-50 text-orange-700',
        'ready_for_delivery', 'in_transit', 'packed' => 'bg-blue-50 text-blue-700',
        'ready_to_pack' => 'bg-orange-50 text-orange-800',
        'refund_pending' => 'bg-red-50 text-red-700',
        'refunded', 'cancelled' => 'bg-slate-100 text-slate-700',
        'awaiting_customer_confirmation' => 'bg-amber-50 text-amber-700',
        'under_review' => 'bg-red-50 text-red-700',
        'awaiting_payment' => 'bg-amber-50 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<div class="flex flex-wrap gap-2">
    @if ($invoice->payment_status !== \App\Models\ShoppingList::PAYMENT_PAID)
        <span class="rounded px-2 py-1 text-xs font-bold {{ $invoiceTone }}">{{ \App\Models\ShoppingList::statuses()[$invoice->status] ?? ucfirst($invoice->status) }}</span>
    @endif
    <span class="rounded px-2 py-1 text-xs font-bold {{ $paymentTone }}">{{ \App\Models\ShoppingList::paymentStatuses()[$invoice->payment_status] ?? ucfirst($invoice->payment_status) }}</span>
    <span class="rounded px-2 py-1 text-xs font-bold {{ $deliveryTone }}">{{ $invoice->fulfilmentStatusLabel() }}</span>
</div>
