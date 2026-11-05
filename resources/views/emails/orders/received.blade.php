<div style="font-family:Arial,sans-serif;line-height:1.6;color:#14213d;max-width:600px;margin:auto">
    <h1 style="color:#c2410c">We received your EduKit order</h1>
    <p>Hello {{ $order->parent_name }},</p>
    <p>Thank you for shopping with EduKit. We received your order <strong>{{ $order->reference }}</strong>.</p>
    @if ($order->customer_user_id)
        <p>Sign in to your customer account to review payment and delivery progress.</p>
        <p><a href="{{ route('customer.dashboard') }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">View my orders</a></p>
    @else
        <p>You placed this order as a guest, so no account is required to pay. To track it later, create an EduKit account or sign in using the email address where you received this message, then verify that email.</p>
        <p><a href="{{ route('customer.register') }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">Create an account</a></p>
    @endif
    <p>If you did not place this order, please contact EduKit support.</p>
    <p>Thank you,<br>EduKit Store</p>
</div>
