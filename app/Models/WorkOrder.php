<?php

namespace App\Models;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Production work order header (M09).
 */
class WorkOrder extends Model
{
    /** @use HasFactory<\Database\Factories\WorkOrderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'item_id',
        'bom_id',
        'planned_quantity',
        'good_quantity',
        'rejected_quantity',
        'sales_order_id',
        'sales_order_item_id',
        'planned_start_date',
        'planned_end_date',
        'source_warehouse_id',
        'target_warehouse_id',
        'work_centre_id',
        'priority',
        'status',
        'bom_version_reason',
        'standard_unit_cost',
        'actual_material_cost',
        'actual_machine_cost',
        'actual_labour_cost',
        'actual_overhead_cost',
        'actual_total_cost',
        'actual_unit_cost',
        'cost_variance',
        'remarks',
        'released_at',
        'released_by',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'planned_quantity' => 'decimal:4',
            'good_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'priority' => WorkOrderPriority::class,
            'status' => WorkOrderStatus::class,
            'standard_unit_cost' => 'decimal:4',
            'actual_material_cost' => 'decimal:2',
            'actual_machine_cost' => 'decimal:2',
            'actual_labour_cost' => 'decimal:2',
            'actual_overhead_cost' => 'decimal:2',
            'actual_total_cost' => 'decimal:2',
            'actual_unit_cost' => 'decimal:4',
            'cost_variance' => 'decimal:2',
            'released_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function workCentre(): BelongsTo
    {
        return $this->belongsTo(WorkCentre::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(WorkOrderComponent::class)->orderBy('sort_order');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(WorkOrderOperation::class)->orderBy('sequence');
    }

    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class);
    }
}
