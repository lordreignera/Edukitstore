<?php

namespace App\Support;

class PaymentMode
{
    public static function demoEnabled(): bool
    {
        if (config('edukit.payment_mode') !== 'demo') {
            return false;
        }

        $localOrTesting = app()->environment(['local', 'testing'])
            && in_array(config('app.env'), ['local', 'testing'], true);

        return $localOrTesting || (bool) config('edukit.allow_demo_payments_online');
    }
}
