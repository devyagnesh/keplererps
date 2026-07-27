<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Production plan line: one finished item quantity to be manufactured.
 *
 * @property int $id
 * @property int $production_plan_id
 * @property int $item_id
 * @property int $bom_id
 * @property int|null $work_order_id
 */
class ProductionPlanItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_plan_id',
        'item_id',
        'bom_id',
        'sales_order_id',
        'sales_order_item_id',
        'work_order_id',
        'planned_quantity',
        'required_date',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'required_date' => 'date',
        ];
    }

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
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

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
