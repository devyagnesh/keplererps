<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Work order routing operation snapshot (M09).
 */
class WorkOrderOperation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_order_id',
        'sequence',
        'requires_qc',
        'manufacturing_operation_id',
        'work_centre_id',
        'setup_time_minutes',
        'run_time_per_unit_minutes',
        'machine_rate_per_hour',
        'labour_rate_per_hour',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_qc' => 'boolean',
            'setup_time_minutes' => 'decimal:2',
            'run_time_per_unit_minutes' => 'decimal:4',
            'machine_rate_per_hour' => 'decimal:4',
            'labour_rate_per_hour' => 'decimal:4',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
