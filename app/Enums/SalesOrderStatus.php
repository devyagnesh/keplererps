<?php

namespace App\Enums;

/**
 * Sales order lifecycle (SRS Appendix A / M06).
 */
enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Confirmed = 'confirmed';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case Invoiced = 'invoiced';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case OnHold = 'on_hold';

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
            self::PendingApproval => 'Pending Approval',
            self::Confirmed => 'Confirmed',
            self::PartiallyDelivered => 'Partially Delivered',
            self::Delivered => 'Delivered',
            self::Invoiced => 'Invoiced',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
            self::OnHold => 'On Hold',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canInvoice(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::PartiallyDelivered,
            self::Delivered,
        ], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::PendingApproval,
            self::Confirmed,
            self::OnHold,
        ], true);
    }
}
