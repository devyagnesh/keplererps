<?php

namespace App\Enums;

/**
 * Purchase indent lifecycle (M07 / US-M07-01).
 */
enum PurchaseIndentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyOrdered = 'partially_ordered';
    case Ordered = 'ordered';
    case Closed = 'closed';
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
            self::PartiallyOrdered => 'Partially Ordered',
            self::Ordered => 'Ordered',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }
}
