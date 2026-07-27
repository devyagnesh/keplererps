<?php

namespace App\Enums;

/**
 * Monthly salary run lifecycle (M14).
 */
enum SalaryRunStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
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
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary-transparent',
            self::Posted => 'bg-success-transparent',
            self::Cancelled => 'bg-danger-transparent',
        };
    }
}
