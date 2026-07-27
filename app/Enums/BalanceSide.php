<?php

namespace App\Enums;

/**
 * Debit / credit side of an accounting balance.
 */
enum BalanceSide: string
{
    case Debit = 'debit';
    case Credit = 'credit';

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
            self::Debit => 'Debit',
            self::Credit => 'Credit',
        };
    }

    public function opposite(): self
    {
        return $this === self::Debit ? self::Credit : self::Debit;
    }
}
