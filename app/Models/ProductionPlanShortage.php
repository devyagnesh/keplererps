<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Component shortage captured when a production plan generates work orders.
 *
 * @property int $id
 * @property int $production_plan_id
 * @property int $item_id
 */
class ProductionPlanShortage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_plan_id',
        'item_id',
        'required_quantity',
        'available_quantity',
        'shortage_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'shortage_quantity' => 'decimal:4',
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
}
