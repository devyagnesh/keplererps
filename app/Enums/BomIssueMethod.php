<?php

namespace App\Enums;

/**
 * How a BOM component is issued to production (M04).
 */
enum BomIssueMethod: string
{
    case Manual = 'manual';
    case Backflush = 'backflush';

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
            self::Manual => 'Manual issue',
            self::Backflush => 'Backflush',
        };
    }
}
