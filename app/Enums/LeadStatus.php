<?php

namespace App\Enums;

/**
 * Lead pipeline stage (M05).
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';

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
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Converted => 'Converted',
            self::Lost => 'Lost',
        };
    }

    /**
     * Only open leads accept edits, follow-ups and conversion.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Contacted, self::Qualified], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'bg-info-transparent',
            self::Contacted => 'bg-primary-transparent',
            self::Qualified => 'bg-warning-transparent',
            self::Converted => 'bg-success-transparent',
            self::Lost => 'bg-danger-transparent',
        };
    }
}
