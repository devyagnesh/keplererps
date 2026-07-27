<?php

namespace App\Enums;

/**
 * QC inspection stage (M10).
 */
enum InspectionType: string
{
    case Incoming = 'incoming';
    case InProcess = 'in_process';
    case Final = 'final';
    case PreDispatch = 'pre_dispatch';
    case CustomerReturn = 'customer_return';

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
            self::Incoming => 'Incoming',
            self::InProcess => 'In-process',
            self::Final => 'Final',
            self::PreDispatch => 'Pre-dispatch',
            self::CustomerReturn => 'Customer return',
        };
    }
}
