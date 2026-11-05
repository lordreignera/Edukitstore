<div style="font-family:Arial,sans-serif;line-height:1.6;color:#14213d;max-width:600px;margin:auto">
    <h1 style="color:#c2410c">Thank you for your payment</h1>
    <p>Hello {{ $order->parent_name }},</p>
    @if ($order->payment_provider === 'demo')
        <p>Demo payment was recorded for order <strong>{{ $order->reference }}</strong>. This was a test transaction and no money was collected.</p>
    @else
        <p>We have confirmed payment for order <strong>{{ $order->reference }}</strong>. Thank you for choosing EduKit. We will continue preparing your order.</p>
    @endif
    @if ($order->payment_exception)
        <p>Our team is reviewing an order detail before fulfilment. You can see the latest update in your account.</p>
    @endif
    <p><strong>Amount:</strong> UGX {{ number_format((int) $order->estimated_total) }}</p>
    @if ($order->customer_user_id)
        <p><a href="{{ route('customer.dashboard') }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">Track my order</a></p>
    @else
        <p>You paid as a guest. To track this order later, create an EduKit account or sign in using the email address where you received this message, then verify that email.</p>
        <p><a href="{{ route('customer.register') }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">Create an account</a></p>
    @endif
    <p>Thank you,<br>EduKit Store</p>
</div>
