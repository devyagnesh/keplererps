<?php

namespace App\Enums;

/**
 * Transport modes for dispatch / e-way bill (M12).
 */
enum TransportMode: string
{
    case Road = 'road';
    case Rail = 'rail';
    case Air = 'air';
    case Ship = 'ship';

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
            self::Road => 'Road',
            self::Rail => 'Rail',
            self::Air => 'Air',
            self::Ship => 'Ship',
        };
    }
}
