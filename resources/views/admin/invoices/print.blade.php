<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order {{ $invoice->reference }} | EduKit Store</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #14233f; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; font-size: 12px; }
        .toolbar { max-width: 900px; margin: 18px auto 10px; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar a, .toolbar button { border: 1px solid #d1d5db; border-radius: 6px; padding: 9px 14px; background: white; color: #14233f; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: #ea580c; background: #ea580c; color: white; }
        .sheet { width: min(900px, calc(100% - 28px)); margin: 0 auto 28px; padding: 30px 34px; background: #fff; box-shadow: 0 2px 10px #0f172a12; }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding-bottom: 16px; border-bottom: 2px solid #ea580c; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo { width: 48px; height: 48px; object-fit: contain; }
        .brand-name { margin: 0; color: #0b245c; font-size: 17px; font-weight: 800; }
        .brand-subtitle { margin: 3px 0 0; color: #64748b; font-size: 10px; }
        .document-title { margin: 0; color: #0b245c; font-size: 18px; font-weight: 800; text-align: right; }
        .reference { margin: 5px 0 0; color: #64748b; text-align: right; }
        .status-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0; }
        .badge { display: inline-block; border: 1px solid #fed7aa; border-radius: 999px; padding: 5px 9px; background: #fff7ed; color: #9a3412; font-size: 10px; font-weight: 800; }
        .badge.paid { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
        .badge.demo { border-color: #fcd34d; background: #fffbeb; color: #92400e; }
        .section-title { margin: 18px 0 8px; color: #0b245c; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        .details { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .detail { min-height: 54px; padding: 9px 11px; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .detail-label { display: block; margin-bottom: 4px; color: #64748b; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .detail-value { color: #0f172a; font-weight: 700; overflow-wrap: anywhere; }
        .driver-box { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-top: 10px; padding: 11px 13px; border: 1px solid #fdba74; border-left: 4px solid #ea580c; border-radius: 5px; background: #fff7ed; }
        .driver-label { color: #9a3412; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .driver-name { margin-top: 4px; color: #0b245c; font-size: 14px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 8px; background: #f1f5f9; color: #475569; font-size: 9px; text-align: left; text-transform: uppercase; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .product-cell { display: flex; align-items: center; gap: 10px; min-width: 220px; }
        .product-image { width: 48px; height: 48px; flex: 0 0 48px; border: 1px solid #e2e8f0; border-radius: 4px; background: #f8fafc; object-fit: contain; }
        .product-name { color: #0b245c; font-weight: 800; }
        .product-meta { margin-top: 3px; color: #64748b; font-size: 10px; }
        .fulfilment { margin-top: 4px; color: #9a3412; font-size: 9px; font-weight: 700; }
        .unavailable { margin-top: 4px; color: #b91c1c; font-size: 9px; font-weight: 700; }
        .money { text-align: right; white-space: nowrap; }
        .totals { width: min(320px, 100%); margin: 12px 0 0 auto; }
        .total-row { display: flex; justify-content: space-between; gap: 16px; padding: 5px 0; }
        .grand-total { margin-top: 4px; padding-top: 9px; border-top: 2px solid #0b245c; color: #0b245c; font-size: 15px; font-weight: 900; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 9px; text-align: center; }
        @media (max-width: 600px) {
            .sheet { padding: 20px 16px; }
            .details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .document-title { font-size: 15px; }
            .product-cell { min-width: 0; }
            th, td { padding: 6px 4px; }
            .product-image { width: 38px; height: 38px; flex-basis: 38px; }
        }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: white; font-size: 10pt; }
            .toolbar { display: none !important; }
            .sheet { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .header, .driver-box, .details, tr { break-inside: avoid; }
            .product-image { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $printItems = $invoice->lineItems->isNotEmpty() ? $invoice->lineItems : collect($invoice->cart_items ?? []);
        $schoolName = $invoice->school?->name ?? $invoice->school_name ?? 'Not provided';
        $deliveryStatus = \App\Models\ShoppingList::deliveryStatuses()[$invoice->deliveryStatus()] ?? ucfirst($invoice->deliveryStatus());
        $hasUnavailableItems = $invoice->packed_at && $invoice->lineItems->contains(fn ($item) => (int) $item->unavailable_quantity > 0);
        $deliverableSubtotal = (int) $invoice->lineItems->sum('line_total');
        $refundAmount = $invoice->manual_refunded_at ? (int) $invoice->manual_refund_amount : (int) $invoice->manualRefundOutstanding();
        $netAfterRefund = max(0, (int) ($invoice->estimated_total ?? 0) - $refundAmount);
        $isDemoPayment = $invoice->payment_provider === 'demo';
    @endphp
    <nav class="toolbar" aria-label="Print controls">
        <a href="{{ route('admin.invoices.show', $invoice) }}">Back to order</a>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </nav>

    <main class="sheet">
        <header class="header">
            <div class="brand">
                <img class="logo" src="{{ asset('images/website/EDUKIT_STORE_logo2.png') }}" alt="EduKit Store logo">
                <div>
                    <p class="brand-name">EduKit Store</p>
                    <p class="brand-subtitle">School items &amp; supplies</p>
                </div>
            </div>
            <div>
                <h1 class="document-title">Order invoice &amp; fulfilment</h1>
                <p class="reference">{{ $invoice->reference }} <span aria-hidden="true">&middot;</span> {{ $invoice->created_at?->format('d M Y, H:i') }}</p>
            </div>
        </header>

        <div class="status-row" aria-label="Order statuses">
            @if ($invoice->payment_status !== \App\Models\ShoppingList::PAYMENT_PAID && $invoice->payment_status !== \App\Models\ShoppingList::PAYMENT_REFUNDED)
                <span class="badge">{{ \App\Models\ShoppingList::statuses()[$invoice->status] ?? ucfirst($invoice->status) }}</span>
            @endif
            <span class="badge {{ $invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'paid' : '' }}">Payment: {{ \App\Models\ShoppingList::paymentStatuses()[$invoice->payment_status] ?? ucfirst($invoice->payment_status) }}</span>
            <span class="badge">Fulfilment: {{ $deliveryStatus }}</span>
            @if ($invoice->payment_provider === 'demo')
                <span class="badge demo">Demo payment — no funds collected</span>
            @endif
        </div>

        <h2 class="section-title">Customer &amp; delivery</h2>
        <section class="details" aria-label="Customer and delivery details">
            <div class="detail"><span class="detail-label">Customer</span><span class="detail-value">{{ $invoice->parent_name }}</span></div>
            <div class="detail"><span class="detail-label">Mobile Money / contact</span><span class="detail-value">{{ $invoice->phone }}</span></div>
            <div class="detail"><span class="detail-label">School</span><span class="detail-value">{{ $schoolName }}</span></div>
            <div class="detail"><span class="detail-label">Learner / class</span><span class="detail-value">{{ $invoice->learner_name ?: '—' }}{{ $invoice->class_level ? ' / '.$invoice->class_level : '' }}</span></div>
            <div class="detail"><span class="detail-label">Delivery method</span><span class="detail-value">{{ ucfirst($invoice->delivery_preference) }}</span></div>
            <div class="detail"><span class="detail-label">Drop-off location</span><span class="detail-value">{{ $invoice->delivery_location ?: '—' }}</span></div>
        </section>

        <section class="driver-box" aria-label="Driver assignment">
            <div>
                <span class="driver-label">Assigned driver</span>
                <div class="driver-name">{{ $invoice->assignedDriver?->name ?? 'Not assigned' }}</div>
            </div>
            <div style="text-align:right">
                <span class="driver-label">Driver contact</span>
                <div class="detail-value" style="margin-top:4px">{{ $invoice->assignedDriver?->phone ?? 'Pending assignment' }}</div>
            </div>
        </section>

        <h2 class="section-title">Products to deliver / unavailable items</h2>
        @if ($printItems->isNotEmpty())
            <table>
                <thead><tr><th>Product</th><th>Deliver / ordered</th><th class="money">Unit price</th><th class="money">Line total</th></tr></thead>
                <tbody>
                    @foreach ($printItems as $item)
                        @php
                            $productName = data_get($item, 'product_name') ?? data_get($item, 'name', 'Order item');
                            $productImage = data_get($item, 'product.image_url') ?? data_get($item, 'image_url');
                            $fulfilmentSource = data_get($item, 'fulfilment_source', 'edukit');
                        @endphp
                        <tr>
                            <td>
                                <div class="product-cell">
                                    @if ($productImage)
                                        <img class="product-image" src="{{ $productImage }}" alt="{{ $productName }}" loading="eager">
                                    @else
                                        <div class="product-image" role="img" aria-label="Product image unavailable"></div>
                                    @endif
                                    <div>
                                        <div class="product-name">{{ $productName }}</div>
                                        <div class="product-meta">SKU: {{ data_get($item, 'sku') ?: '—' }}</div>
                                        <div class="fulfilment">{{ $fulfilmentSource === 'supplier' ? 'Supplier direct fulfilment' : 'EduKit warehouse fulfilment' }}</div>
                                        @if ((int) data_get($item, 'unavailable_quantity') > 0)
                                            <div class="unavailable">{{ data_get($item, 'unavailable_quantity') }} unavailable — {{ \App\Models\ShoppingListItem::unavailableReasons()[data_get($item, 'unavailable_reason')] ?? 'Other reason' }}{{ data_get($item, 'unavailable_note') ? ': '.data_get($item, 'unavailable_note') : '' }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format((int) data_get($item, 'quantity')) }} / {{ number_format((int) (data_get($item, 'ordered_quantity') ?: data_get($item, 'quantity'))) }}</td>
                            <td class="money">UGX {{ number_format((float) data_get($item, 'unit_price')) }}</td>
                            <td class="money"><strong>UGX {{ number_format((float) data_get($item, 'line_total')) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No priced product lines are attached to this order yet.</p>
        @endif

        <section class="totals" aria-label="Invoice totals">
            @if ($hasUnavailableItems)
                <div class="total-row"><span>Deliverable items subtotal</span><strong>UGX {{ number_format($deliverableSubtotal) }}</strong></div>
                <div class="total-row"><span>Convenience fee charged</span><strong>{{ $invoice->delivery_fee === null ? 'Pending' : 'UGX '.number_format((float) $invoice->delivery_fee) }}</strong></div>
                <div class="total-row"><span>{{ $isDemoPayment ? 'Demo order total (no funds collected)' : 'Original amount paid' }}</span><strong>UGX {{ number_format((float) $invoice->estimated_total) }}</strong></div>
                <div class="total-row"><span>{{ $isDemoPayment ? 'Dry-run refund '.($invoice->manual_refunded_at ? 'recorded' : 'due') : 'Manual refund '.($invoice->manual_refunded_at ? 'sent' : 'due') }}</span><strong>UGX {{ number_format($refundAmount) }}</strong></div>
                <div class="total-row grand-total"><span>{{ $invoice->manual_refunded_at ? 'Net amount after refund' : 'Expected amount after refund' }}</span><span>UGX {{ number_format($netAfterRefund) }}</span></div>
                @if ($invoice->manual_refund_reference)<div class="total-row"><span>Refund reference</span><strong>{{ $invoice->manual_refund_reference }}</strong></div>@endif
            @else
                <div class="total-row"><span>Items subtotal</span><strong>UGX {{ number_format((float) $invoice->items_subtotal) }}</strong></div>
                <div class="total-row"><span>Convenience fee</span><strong>{{ $invoice->delivery_fee === null ? 'Pending' : 'UGX '.number_format((float) $invoice->delivery_fee) }}</strong></div>
                <div class="total-row grand-total"><span>Order total</span><span>{{ $invoice->estimated_total === null ? 'Pending' : 'UGX '.number_format((float) $invoice->estimated_total) }}</span></div>
            @endif
        </section>

        @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
            <p class="product-meta" style="margin-top:12px">Paid {{ $invoice->paid_at?->format('d M Y, H:i') ?: '' }}{{ $invoice->payment_reference ? ' · Payment reference: '.$invoice->payment_reference : '' }}</p>
        @endif
        @if ($invoice->notes)
            <p class="product-meta" style="margin-top:10px"><strong>Order notes:</strong> {{ $invoice->notes }}</p>
        @endif
        <footer class="footer">EduKit Store <span aria-hidden="true">&middot;</span> Keep this sheet with the order while it is being prepared and delivered.</footer>
    </main>
</body>
</html>
