<?php

namespace App\Enums;

/**
 * Maintenance order type (M11).
 */
enum MaintenanceOrderType: string
{
    case Preventive = 'preventive';
    case Breakdown = 'breakdown';

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
            self::Preventive => 'Preventive',
            self::Breakdown => 'Breakdown',
        };
    }

    public function assetStatusWhileOpen(): AssetStatus
    {
        return match ($this) {
            self::Preventive => AssetStatus::UnderMaintenance,
            self::Breakdown => AssetStatus::UnderBreakdown,
        };
    }
}
