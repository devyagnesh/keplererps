<?php

namespace App\Enums;

/**
 * RFQ lifecycle (M07).
 */
enum RfqStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Quoted = 'quoted';
    case Awarded = 'awarded';
    case Cancelled = 'cancelled';

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
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Quoted => 'Quoted',
            self::Awarded => 'Awarded',
            self::Cancelled => 'Cancelled',
        };
    }
}
