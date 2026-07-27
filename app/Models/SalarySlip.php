<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's earnings for a payroll run (M14).
 *
 * @property int $salary_run_id
 * @property int $employee_id
 * @property string $net_amount
 */
class SalarySlip extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'salary_run_id',
        'employee_id',
        'payable_days',
        'period_days',
        'overtime_hours',
        'pieces',
        'basic_amount',
        'allowance_amount',
        'overtime_amount',
        'piece_amount',
        'gross_amount',
        'deduction_amount',
        'net_amount',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payable_days' => 'decimal:2',
            'period_days' => 'integer',
            'overtime_hours' => 'decimal:2',
            'pieces' => 'decimal:4',
            'basic_amount' => 'decimal:2',
            'allowance_amount' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'piece_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function salaryRun(): BelongsTo
    {
        return $this->belongsTo(SalaryRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
