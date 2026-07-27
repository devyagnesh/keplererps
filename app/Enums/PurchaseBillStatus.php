<?php

namespace App\Enums;

/**
 * Supplier purchase bill lifecycle (US-M07-04).
 */
enum PurchaseBillStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
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
            self::Approved => 'Approved',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Only draft bills may be edited or deleted.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
