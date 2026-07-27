<?php

namespace App\Enums;

/**
 * How additional receipt charges are spread across goods receipt lines (M08 valuation).
 */
enum ChargeAllocationBasis: string
{
    case Value = 'value';
    case Quantity = 'quantity';

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
            self::Value => 'Line value',
            self::Quantity => 'Accepted quantity',
        };
    }
}
