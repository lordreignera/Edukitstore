<div class="rounded border border-slate-200 bg-slate-50 p-4 text-sm">
    <div class="flex justify-between">
        <span class="font-semibold text-slate-600">Items subtotal</span>
        <span class="font-black text-slate-950">UGX {{ number_format($invoice->items_subtotal) }}</span>
    </div>
    <div class="mt-2 flex justify-between">
        <span class="font-semibold text-slate-600">Delivery/convenience</span>
        <span class="font-black text-slate-950">{{ $invoice->delivery_fee === null ? 'Pending' : 'UGX '.number_format($invoice->delivery_fee) }}</span>
    </div>
    <div class="mt-2 flex justify-between border-t border-slate-200 pt-2">
        <span class="font-black text-[#07215f]">Invoice total</span>
        <span class="font-black text-[#07215f]">{{ $invoice->estimated_total ? 'UGX '.number_format($invoice->estimated_total) : 'Pending' }}</span>
    </div>
</div>
