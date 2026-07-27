<?php

namespace App\Enums;

/**
 * Sampling plan for QC lot inspection (US-M10-02).
 */
enum SamplingPlanType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case SqrtPlusOne = 'sqrt_plus_one';

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
            self::Fixed => 'Fixed quantity',
            self::Percentage => 'Percentage of lot',
            self::SqrtPlusOne => '√n + 1',
        };
    }
}
