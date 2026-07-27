<?php

namespace App\Enums;

/**
 * Delivery challan lifecycle (SRS Appendix A / M12).
 */
enum DeliveryChallanStatus: string
{
    case Draft = 'draft';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
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
            self::Dispatched => 'Dispatched',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canInvoice(): bool
    {
        return in_array($this, [self::Dispatched, self::Delivered], true);
    }
}
