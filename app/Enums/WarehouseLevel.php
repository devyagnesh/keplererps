<?php

namespace App\Enums;

/**
 * Warehouse hierarchy levels: Plant → Store → Rack → Bin.
 */
enum WarehouseLevel: string
{
    case Plant = 'plant';
    case Store = 'store';
    case Rack = 'rack';
    case Bin = 'bin';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Maximum nesting depth allowed by SRS BR-04 / US-M01-02.
     */
    public function depth(): int
    {
        return match ($this) {
            self::Plant => 1,
            self::Store => 2,
            self::Rack => 3,
            self::Bin => 4,
        };
    }
}
