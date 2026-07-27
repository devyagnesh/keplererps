<?php

namespace App\Models;

use App\Enums\SalaryRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Monthly payroll run that groups the salary slips of a period (M14).
 *
 * @property string $document_no
 * @property int $period_year
 * @property int $period_month
 * @property SalaryRunStatus $status
 */
class SalaryRun extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'period_year',
        'period_month',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'employee_count',
        'gross_total',
        'deduction_total',
        'net_total',
        'remarks',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
            'status' => SalaryRunStatus::class,
            'employee_count' => 'integer',
            'gross_total' => 'decimal:2',
            'deduction_total' => 'decimal:2',
            'net_total' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function slips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Human-readable payroll period, e.g. "Jul 2026".
     */
    public function periodLabel(): string
    {
        return date('M Y', mktime(0, 0, 0, $this->period_month, 1, $this->period_year));
    }
}
