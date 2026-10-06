<?php

return [
    'initial_admin_email' => env('EDUKIT_INITIAL_ADMIN_EMAIL') ?: 'superadmin@edukit.test',
    'initial_admin_password' => env('EDUKIT_INITIAL_ADMIN_PASSWORD') ?: 'password',
    'payment_mode' => env('EDUKIT_PAYMENT_MODE', 'flutterwave'),
];
