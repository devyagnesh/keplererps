<?php

namespace App\Enums;

/**
 * Disposition for rejected production quantity (US-M09-06).
 */
enum RejectionDisposition: string
{
    case Rework = 'rework';
    case Scrap = 'scrap';
    case Downgrade = 'downgrade';

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
            self::Rework => 'Rework',
            self::Scrap => 'Scrap',
            self::Downgrade => 'Downgrade',
        };
    }
}
