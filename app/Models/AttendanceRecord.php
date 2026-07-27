<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attendance marking per employee per day (M14).
 *
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $attendance_date
 * @property AttendanceStatus $status
 */
class AttendanceRecord extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'shift_id',
        'status',
        'worked_hours',
        'overtime_hours',
        'remarks',
        'source',
        'punch_in_at',
        'punch_out_at',
        'latitude',
        'longitude',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => AttendanceStatus::class,
            'worked_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'punch_in_at' => 'datetime',
            'punch_out_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
