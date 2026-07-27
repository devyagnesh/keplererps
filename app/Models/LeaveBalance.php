<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Annual leave balance per employee (M14).
 */
class LeaveBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'year',
        'leave_type',
        'opening_days',
        'availed_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_days' => 'decimal:2',
            'availed_days' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
