<?php

namespace App\Enums;

/**
 * Direction of a stock adjustment line relative to physical quantity.
 */
enum AdjustmentDirection: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

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
            self::Increase => 'Increase',
            self::Decrease => 'Decrease',
        };
    }
}
