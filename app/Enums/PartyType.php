<?php

namespace App\Enums;

/**
 * Party classification for customers and suppliers.
 */
enum PartyType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both = 'both';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
