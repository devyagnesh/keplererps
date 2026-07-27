<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Shift;

/**
 * Indian statutory payroll deductions and piece/overtime helpers (M14).
 */
class StatutoryPayrollService
{
    private const ESI_GROSS_CEILING = 21000.0;

    /**
     * Employee PF contribution — 12% of basic when applicable.
     */
    public function pfEmployee(float $basic): float
    {
        if ($basic <= 0) {
            return 0.0;
        }

        return round($basic * 0.12, 2);
    }

    /**
     * Employer PF contribution — 13% of basic (12% + admin) when applicable.
     */
    public function pfEmployer(float $basic): float
    {
        if ($basic <= 0) {
            return 0.0;
        }

        return round($basic * 0.13, 2);
    }

    /**
     * Employee ESI — 0.75% of gross when gross is within the statutory ceiling.
     */
    public function esiEmployee(float $gross): float
    {
        if ($gross <= 0 || $gross > self::ESI_GROSS_CEILING) {
            return 0.0;
        }

        return round($gross * 0.0075, 2);
    }

    /**
     * Employer ESI — 3.25% of gross when gross is within the statutory ceiling.
     */
    public function esiEmployer(float $gross): float
    {
        if ($gross <= 0 || $gross > self::ESI_GROSS_CEILING) {
            return 0.0;
        }

        return round($gross * 0.0325, 2);
    }

    /**
     * Professional tax stub slabs for Gujarat and Maharashtra.
     */
    public function professionalTax(?string $state, float $gross): float
    {
        $stateCode = strtoupper(trim((string) $state));

        return match ($stateCode) {
            'GJ', 'GUJARAT' => $gross >= 12000 ? 200.0 : 0.0,
            'MH', 'MAHARASHTRA' => $gross >= 7500 ? 200.0 : 0.0,
            default => 0.0,
        };
    }

    /**
     * Piece-rate earnings for production workers.
     *
     * @param  float|null  $rateOverride  Item-level rate when set; otherwise employee piece_rate.
     */
    public function pieceEarnings(Employee $employee, float $pieces, ?float $rateOverride = null): float
    {
        $rate = $rateOverride ?? (float) ($employee->piece_rate ?? 0);

        return round($rate * $pieces, 2);
    }

    /**
     * Overtime hours beyond the shift threshold or configured OT start.
     */
    public function computeOvertimeHours(float $workedHours, ?Shift $shift): float
    {
        if ($workedHours <= 0) {
            return 0.0;
        }

        $threshold = $shift !== null
            ? (float) ($shift->ot_after_hours ?? $shift->durationHours())
            : 0.0;

        if ($workedHours <= $threshold) {
            return 0.0;
        }

        return round($workedHours - $threshold, 2);
    }

    /**
     * Combined employee-side statutory and fixed deductions.
     *
     * @return array{pf: float, esi: float, pt: float, fixed: float, total: float}
     */
    public function deductions(Employee $employee, float $basic, float $gross): array
    {
        $pf = ($employee->pf_applicable ?? true) ? $this->pfEmployee($basic) : 0.0;
        $esi = ($employee->esi_applicable ?? false) ? $this->esiEmployee($gross) : 0.0;
        $pt = $this->professionalTax($employee->pt_state ?? null, $gross);
        $fixed = round((float) ($employee->fixed_deduction ?? 0), 2);

        return [
            'pf' => $pf,
            'esi' => $esi,
            'pt' => $pt,
            'fixed' => $fixed,
            'total' => round($pf + $esi + $pt + $fixed, 2),
        ];
    }
}
