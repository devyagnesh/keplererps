<?php

namespace App\Enums;

/**
 * Record visibility scope for a user.
 */
enum DataScopeType: string
{
    case All = 'all';
    case Branch = 'branch';
    case Team = 'team';
    case Own = 'own';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
