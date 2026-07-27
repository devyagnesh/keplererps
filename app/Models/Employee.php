<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee master (M14).
 *
 * @property int $id
 * @property string $employee_code
 * @property string $full_name
 * @property EmploymentStatus $status
 * @property string $monthly_gross
 * @property string $basic_percent
 */
class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_code',
        'full_name',
        'designation',
        'department',
        'branch_id',
        'shift_id',
        'user_id',
        'mobile',
        'email',
        'date_of_joining',
        'date_of_birth',
        'date_of_exit',
        'status',
        'monthly_gross',
        'basic_percent',
        'fixed_deduction',
        'overtime_rate_per_hour',
        'pf_applicable',
        'esi_applicable',
        'pt_state',
        'piece_rate',
        'bank_account_no',
        'ifsc_code',
        'pan',
        'uan',
        'pf_number',
        'esi_number',
        'aadhaar_last4',
        'biometric_code',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmploymentStatus::class,
            'date_of_joining' => 'date',
            'date_of_birth' => 'date',
            'date_of_exit' => 'date',
            'monthly_gross' => 'decimal:2',
            'basic_percent' => 'decimal:2',
            'fixed_deduction' => 'decimal:2',
            'overtime_rate_per_hour' => 'decimal:2',
            'pf_applicable' => 'boolean',
            'esi_applicable' => 'boolean',
            'piece_rate' => 'decimal:4',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }

    /**
     * Monthly basic pay derived from the gross and the configured basic share.
     */
    public function basicAmount(): float
    {
        return round((float) $this->monthly_gross * ((float) $this->basic_percent / 100), 2);
    }

    /**
     * Allowances are whatever the gross leaves over after basic pay.
     */
    public function allowanceAmount(): float
    {
        return round((float) $this->monthly_gross - $this->basicAmount(), 2);
    }

    /**
     * Whether the employee was on the rolls on the given date.
     */
    public function isOnRollsOn(string $date): bool
    {
        if ($this->date_of_joining !== null && $this->date_of_joining->gt($date)) {
            return false;
        }

        return $this->date_of_exit === null || $this->date_of_exit->gte($date);
    }
}
