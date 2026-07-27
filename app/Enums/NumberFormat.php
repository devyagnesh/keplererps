<?php

namespace App\Enums;

/**
 * Numeric grouping style for UI and exports.
 */
enum NumberFormat: string
{
    case Indian = 'indian';
    case International = 'international';

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
            self::Indian => 'Indian (12,34,567.89)',
            self::International => 'International (1,234,567.89)',
        };
    }
}
