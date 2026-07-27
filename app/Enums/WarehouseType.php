<?php

namespace App\Enums;

/**
 * Warehouse operational classification (SRS data model).
 */
enum WarehouseType: string
{
    case Store = 'store';
    case Quarantine = 'quarantine';
    case Wip = 'wip';
    case Rejection = 'rejection';
    case WithVendor = 'with_vendor';
    case InTransit = 'in_transit';

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
            self::Store => 'Store',
            self::Quarantine => 'Quarantine',
            self::Wip => 'WIP',
            self::Rejection => 'Rejection',
            self::WithVendor => 'With Vendor',
            self::InTransit => 'In Transit',
        };
    }

    /**
     * Whether stock in this warehouse counts toward available (free) quantity.
     */
    public function countsAsAvailable(): bool
    {
        return $this === self::Store;
    }
}
