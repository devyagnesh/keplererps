<?php

namespace App\Enums;

/**
 * Asset operational status (M11 / US-M11-02).
 */
enum AssetStatus: string
{
    case Active = 'active';
    case UnderMaintenance = 'under_maintenance';
    case UnderBreakdown = 'under_breakdown';
    case Retired = 'retired';

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
            self::Active => 'Active',
            self::UnderMaintenance => 'Under maintenance',
            self::UnderBreakdown => 'Under breakdown',
            self::Retired => 'Retired',
        };
    }

    public function canReceiveProduction(): bool
    {
        return $this === self::Active;
    }

    public function isStopped(): bool
    {
        return in_array($this, [self::UnderMaintenance, self::UnderBreakdown], true);
    }
}
