<?php

namespace App\Enums;

/**
 * How a CRM follow-up was carried out (M05).
 */
enum FollowUpMode: string
{
    case Call = 'call';
    case Email = 'email';
    case Visit = 'visit';
    case WhatsApp = 'whatsapp';
    case Other = 'other';

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
            self::Call => 'Call',
            self::Email => 'Email',
            self::Visit => 'Visit',
            self::WhatsApp => 'WhatsApp',
            self::Other => 'Other',
        };
    }
}
