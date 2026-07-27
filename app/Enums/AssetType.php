<?php

namespace App\Enums;

/**
 * Asset / work-centre classification (M11).
 */
enum AssetType: string
{
    case Machine = 'machine';
    case Mould = 'mould';
    case Die = 'die';
    case Tool = 'tool';
    case Utility = 'utility';

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
            self::Machine => 'Machine',
            self::Mould => 'Mould',
            self::Die => 'Die',
            self::Tool => 'Tool',
            self::Utility => 'Utility',
        };
    }

    public function tracksCycles(): bool
    {
        return in_array($this, [self::Mould, self::Die], true);
    }
}
