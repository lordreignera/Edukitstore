<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UgandaMobileMoneyNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (str_starts_with($digits, '256')) {
            $subscriberNumber = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $subscriberNumber = substr($digits, 1);
        } else {
            $subscriberNumber = $digits;
        }

        if (! preg_match('/^7\d{8}$/', $subscriberNumber)) {
            $fail('Enter a valid Ugandan mobile number, such as 0700 123 456 or +256 700 123 456.');
        }
    }

    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (str_starts_with($digits, '256')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+256'.substr($digits, 1);
        }

        return '+256'.$digits;
    }
}
