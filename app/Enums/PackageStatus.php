<?php

namespace App\Enums;

/**
 * Lifecycle of a physical package label (M17).
 */
enum PackageStatus: string
{
    case Packed = 'packed';
    case Verified = 'verified';
    case Dispatched = 'dispatched';
    case Cancelled = 'cancelled';

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
            self::Packed => 'Packed',
            self::Verified => 'Scan Verified',
            self::Dispatched => 'Dispatched',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Packages that still count towards a challan's packed quantity.
     */
    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Packed => 'bg-info-transparent',
            self::Verified => 'bg-warning-transparent',
            self::Dispatched => 'bg-success-transparent',
            self::Cancelled => 'bg-danger-transparent',
        };
    }
}
