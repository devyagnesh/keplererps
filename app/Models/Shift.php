<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Work shift master (M14).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 */
class Shift extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'ot_after_hours',
        'ot_multiplier',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'break_minutes' => 0,
        'ot_multiplier' => 1.5,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'ot_after_hours' => 'decimal:2',
            'ot_multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Paid hours of the shift, spanning midnight when the end time is earlier than the start.
     */
    public function durationHours(): float
    {
        $start = strtotime('1970-01-01 '.$this->start_time.' UTC');
        $end = strtotime('1970-01-01 '.$this->end_time.' UTC');

        if ($end <= $start) {
            $end += 86400;
        }

        return round((($end - $start) / 3600) - ((int) $this->break_minutes / 60), 2);
    }
}
