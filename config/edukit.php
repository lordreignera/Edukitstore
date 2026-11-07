<?php

return [
    'seed_demo_accounts' => env('EDUKIT_SEED_DEMO_ACCOUNTS', false),
    'allow_demo_accounts_online' => env('EDUKIT_ALLOW_DEMO_ACCOUNTS_ONLINE', false),
    'payment_mode' => env('EDUKIT_PAYMENT_MODE', 'flutterwave'),
    'allow_demo_payments_online' => env('EDUKIT_ALLOW_DEMO_PAYMENTS_ONLINE', false),
    'go_live_reset_enabled' => env('EDUKIT_GO_LIVE_RESET_ENABLED', false),
];
