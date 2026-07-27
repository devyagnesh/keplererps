<?php

namespace App\Enums;

/**
 * GST registration type for company and party masters.
 */
enum GstType: string
{
    case Registered = 'registered';
    case Unregistered = 'unregistered';
    case Composition = 'composition';
    case Sez = 'sez';
    case Export = 'export';
    case DeemedExport = 'deemed_export';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
