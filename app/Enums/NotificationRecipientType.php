<?php

namespace App\Enums;

/**
 * How a notification rule resolves its audience (M16).
 */
enum NotificationRecipientType: string
{
    case Role = 'role';
    case Permission = 'permission';

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Role => 'Role',
            self::Permission => 'Permission',
        };
    }
}
