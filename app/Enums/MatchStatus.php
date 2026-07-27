<?php

namespace App\Enums;

/**
 * Three-way match outcome for a purchase bill or bill line (US-M07-04).
 */
enum MatchStatus: string
{
    case Matched = 'matched';
    case RateMismatch = 'rate_mismatch';
    case QtyMismatch = 'qty_mismatch';
    case RateAndQtyMismatch = 'rate_and_qty_mismatch';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Matched => 'Matched',
            self::RateMismatch => 'Rate mismatch',
            self::QtyMismatch => 'Quantity mismatch',
            self::RateAndQtyMismatch => 'Rate and quantity mismatch',
        };
    }

    public function isMatched(): bool
    {
        return $this === self::Matched;
    }

    /**
     * Combine a rate and quantity outcome into a single status.
     */
    public static function fromFlags(bool $rateMismatch, bool $qtyMismatch): self
    {
        return match (true) {
            $rateMismatch && $qtyMismatch => self::RateAndQtyMismatch,
            $rateMismatch => self::RateMismatch,
            $qtyMismatch => self::QtyMismatch,
            default => self::Matched,
        };
    }
}
