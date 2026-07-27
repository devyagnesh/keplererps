<?php

namespace App\Enums;

/**
 * Inventory valuation method (locked after first FY close).
 */
enum CostingMethod: string
{
    case WeightedAverage = 'weighted_average';
    case Fifo = 'fifo';
    case BatchSpecific = 'batch_specific';
    case StandardCost = 'standard_cost';

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
            self::WeightedAverage => 'Weighted Average',
            self::Fifo => 'FIFO',
            self::BatchSpecific => 'Batch / Specific Cost',
            self::StandardCost => 'Standard Cost',
        };
    }
}
