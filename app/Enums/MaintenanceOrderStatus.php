<?php

namespace App\Enums;

/**
 * Maintenance order lifecycle (M11).
 */
enum MaintenanceOrderStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
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
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }

    public function isOpen(): bool
    {
        return $this->isEditable();
    }
}
