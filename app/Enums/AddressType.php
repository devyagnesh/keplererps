<?php

namespace App\Enums;

/**
 * Address role tags for party addresses.
 */
enum AddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Factory = 'factory';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
