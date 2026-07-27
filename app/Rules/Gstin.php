<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Indian GSTIN including checksum and optional PAN alignment.
 */
class Gstin implements ValidationRule
{
    /**
     * @param  string|null  $pan  When provided, characters 3–12 of GSTIN must equal PAN.
     */
    public function __construct(protected ?string $pan = null) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid GSTIN.');

            return;
        }

        $gstin = strtoupper(trim($value));

        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            $fail('The :attribute format is invalid.');

            return;
        }

        if (! $this->passesChecksum($gstin)) {
            $fail('The :attribute checksum is invalid.');

            return;
        }

        if ($this->pan !== null && $this->pan !== '') {
            $pan = strtoupper(trim($this->pan));
            if (substr($gstin, 2, 10) !== $pan) {
                $fail('The :attribute characters 3–12 must match the PAN.');
            }
        }
    }

    /**
     * Verify the GSTIN check digit.
     */
    protected function passesChecksum(string $gstin): bool
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $factor = 1;
        $sum = 0;
        $codePointChars = str_split($chars);

        for ($i = 0; $i < 14; $i++) {
            $codePoint = array_search($gstin[$i], $codePointChars, true);
            if ($codePoint === false) {
                return false;
            }
            $digit = $codePoint * $factor;
            $factor = $factor === 1 ? 2 : 1;
            $digit = (int) ($digit / 36) + ($digit % 36);
            $sum += $digit;
        }

        $checkCodePoint = (36 - ($sum % 36)) % 36;

        return $gstin[14] === $codePointChars[$checkCodePoint];
    }
}
