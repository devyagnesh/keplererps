<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BOM routing / operation line.
 *
 * @property int $id
 * @property int $bom_id
 * @property int $sequence
 * @property int $manufacturing_operation_id
 * @property int|null $work_centre_id
 * @property string $setup_time_minutes
 * @property string $run_time_per_unit_minutes
 * @property string $machine_rate_per_hour
 * @property string $labour_rate_per_hour
 * @property int $operators_required
 * @property bool $is_outsourced
 * @property int|null $vendor_id
 * @property string|null $outsourced_rate
 * @property bool $quality_check_required
 */
class BomOperation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'bom_id',
        'sequence',
        'manufacturing_operation_id',
        'work_centre_id',
        'setup_time_minutes',
        'run_time_per_unit_minutes',
        'machine_rate_per_hour',
        'labour_rate_per_hour',
        'operators_required',
        'is_outsourced',
        'vendor_id',
        'outsourced_rate',
        'quality_check_required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'setup_time_minutes' => 'decimal:2',
            'run_time_per_unit_minutes' => 'decimal:4',
            'machine_rate_per_hour' => 'decimal:2',
            'labour_rate_per_hour' => 'decimal:2',
            'is_outsourced' => 'boolean',
            'outsourced_rate' => 'decimal:4',
            'quality_check_required' => 'boolean',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function manufacturingOperation(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOperation::class);
    }

    public function workCentre(): BelongsTo
    {
        return $this->belongsTo(WorkCentre::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'vendor_id');
    }
}
