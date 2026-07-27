<?php

namespace App\Enums;

/**
 * QC inspection disposition (M10).
 */
enum QcDisposition: string
{
    case Accept = 'accept';
    case AcceptWithDeviation = 'accept_with_deviation';
    case Rework = 'rework';
    case Reject = 'reject';
    case ReturnToSupplier = 'return_to_supplier';

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
            self::Accept => 'Accept',
            self::AcceptWithDeviation => 'Accept with deviation',
            self::Rework => 'Rework',
            self::Reject => 'Reject',
            self::ReturnToSupplier => 'Return to supplier',
        };
    }

    public function movesToStore(): bool
    {
        return in_array($this, [self::Accept, self::AcceptWithDeviation], true);
    }

    public function movesToRejection(): bool
    {
        return in_array($this, [self::Reject, self::ReturnToSupplier], true);
    }
}
