<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Indian 6-digit PIN code (first digit 1–9).
 */
class IndianPinCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('The :attribute must be a valid PIN code.');

            return;
        }

        if (! preg_match('/^[1-9][0-9]{5}$/', (string) $value)) {
            $fail('The :attribute must be exactly 6 digits and not start with 0.');
        }
    }
}
