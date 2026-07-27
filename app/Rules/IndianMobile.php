<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Indian mobile number (10 digits, first digit 6–9).
 */
class IndianMobile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/[\s\-]/', '', (string) $value);
        $digits = ltrim((string) $digits, '+');

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (! preg_match('/^[6-9][0-9]{9}$/', $digits)) {
            $fail('The :attribute must be a valid 10-digit Indian mobile number.');
        }
    }
}
