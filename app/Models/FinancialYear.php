<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Accounting / inventory financial year.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon $starts_on
 * @property \Illuminate\Support\Carbon $ends_on
 * @property bool $is_current
 * @property bool $is_closed
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $closed_by
 */
class FinancialYear extends Model
{
    /** @use HasFactory<\Database\Factories\FinancialYearFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'starts_on',
        'ends_on',
        'is_current',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_current' => false,
        'is_closed' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function documentSeries(): HasMany
    {
        return $this->hasMany(DocumentNumberSeries::class);
    }
}
