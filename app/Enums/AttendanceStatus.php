<?php

namespace App\Enums;

/**
 * Daily attendance marking (M14).
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case HalfDay = 'half_day';
    case PaidLeave = 'paid_leave';
    case UnpaidLeave = 'unpaid_leave';
    case Absent = 'absent';
    case WeeklyOff = 'weekly_off';
    case Holiday = 'holiday';

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
            self::Present => 'Present',
            self::HalfDay => 'Half Day',
            self::PaidLeave => 'Paid Leave',
            self::UnpaidLeave => 'Unpaid Leave',
            self::Absent => 'Absent',
            self::WeeklyOff => 'Weekly Off',
            self::Holiday => 'Holiday',
        };
    }

    /**
     * Payable portion of a day, used by the salary run to prorate earnings.
     */
    public function payableFraction(): float
    {
        return match ($this) {
            self::Present, self::PaidLeave, self::WeeklyOff, self::Holiday => 1.0,
            self::HalfDay => 0.5,
            self::UnpaidLeave, self::Absent => 0.0,
        };
    }

    /**
     * Whether the marking counts towards physical attendance on the shop floor.
     */
    public function isWorked(): bool
    {
        return in_array($this, [self::Present, self::HalfDay], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Present => 'bg-success-transparent',
            self::HalfDay => 'bg-info-transparent',
            self::PaidLeave, self::WeeklyOff, self::Holiday => 'bg-primary-transparent',
            self::UnpaidLeave, self::Absent => 'bg-danger-transparent',
        };
    }
}
