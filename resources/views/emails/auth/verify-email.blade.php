<div style="font-family:Arial,sans-serif;line-height:1.6;color:#14213d;max-width:600px;margin:auto">
    <h1 style="color:#c2410c">Verify your email</h1>
    <p>Hello {{ $recipientName }},</p>
    <p>Please confirm this email address to access your EduKit customer account and track orders linked to it. You can still shop and pay as a guest.</p>
    <p>
        <a href="{{ $verificationUrl }}" style="display:inline-block;background:#c2410c;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">
            Verify email address
        </a>
    </p>
    <p>If the button does not work, copy and paste this link into your browser:</p>
    <p style="word-break:break-all"><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
    <p>This secure link expires after {{ (int) config('auth.verification.expire', 60) }} minutes. If you did not create an EduKit account, you can ignore this email.</p>
    <p>Thank you,<br>EduKit Store</p>
</div>
