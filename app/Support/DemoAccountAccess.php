<?php

namespace App\Support;

use Illuminate\Support\Str;

class DemoAccountAccess
{
    public static function shouldSeed(): bool
    {
        return (bool) config('edukit.seed_demo_accounts')
            && (
                app()->environment(['local', 'testing'])
                || (bool) config('edukit.allow_demo_accounts_online')
            );
    }

    public static function isAllowed(string $email): bool
    {
        if (! Str::endsWith(Str::lower($email), '@edukit.test')) {
            return true;
        }

        return app()->environment(['local', 'testing']) || self::shouldSeed();
    }
}
