<?php

namespace App\Enums;

/**
 * Work order lifecycle (SRS Appendix A / M09).
 */
enum WorkOrderStatus: string
{
    case Draft = 'draft';
    case Released = 'released';
    case InProgress = 'in_progress';
    case Completed = 'completed';
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
            self::Released => 'Released',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
            self::OnHold => 'On Hold',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canRelease(): bool
    {
        return $this === self::Draft;
    }

    public function canReceiveProduction(): bool
    {
        return in_array($this, [self::Released, self::InProgress], true);
    }

    public function canClose(): bool
    {
        return in_array($this, [self::Completed, self::InProgress], true);
    }
}
