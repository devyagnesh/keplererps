<?php

namespace App\Enums;

/**
 * Inventory identity tracking mode for an item.
 */
enum TrackingType: string
{
    case None = 'none';
    case Batch = 'batch';
    case Serial = 'serial';
    case BatchSerial = 'batch_serial';

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
            self::None => 'None',
            self::Batch => 'Batch',
            self::Serial => 'Serial',
            self::BatchSerial => 'Batch + Serial',
        };
    }
}
