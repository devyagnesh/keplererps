<?php

namespace App\Enums;

/**
 * Employment lifecycle of an employee record (M14).
 */
enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

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
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }

    /**
     * Only employees still on the rolls are paid or marked present.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::Active, self::OnLeave], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success-transparent',
            self::OnLeave => 'bg-warning-transparent',
            self::Resigned, self::Terminated => 'bg-danger-transparent',
        };
    }
}
