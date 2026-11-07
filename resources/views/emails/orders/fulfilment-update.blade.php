<div style="font-family:Arial,sans-serif;line-height:1.6;color:#14213d;max-width:600px;margin:auto">
    <h1 style="color:#c2410c">{{ $title }}</h1>
    <p>Hello {{ $order->parent_name }},</p>
    <p>{{ $bodyText }}</p>
    <p><strong>Order:</strong> {{ $order->reference }}</p>
    @if ($order->customer_user_id)
        <p><a href="{{ route('customer.dashboard') }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">View my orders</a></p>
    @else
        <p>Keep this order reference to follow the update on your order page.</p>
    @endif
    <p>If you have questions, contact EduKit Store support.</p>
    <p>Thank you,<br>EduKit Store</p>
</div>
