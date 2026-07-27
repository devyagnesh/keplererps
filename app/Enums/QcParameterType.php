<?php

namespace App\Enums;

/**
 * QC template parameter value types (M10).
 */
enum QcParameterType: string
{
    case Numeric = 'numeric';
    case PassFail = 'pass_fail';
    case Text = 'text';

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
            self::Numeric => 'Numeric',
            self::PassFail => 'Pass / Fail',
            self::Text => 'Text observation',
        };
    }
}
