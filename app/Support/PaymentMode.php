<?php

namespace App\Support;

class PaymentMode
{
    public static function demoEnabled(): bool
    {
        return config('edukit.payment_mode') === 'demo'
            && app()->environment('local', 'testing')
            && in_array(config('app.env'), ['local', 'testing'], true);
    }
}
