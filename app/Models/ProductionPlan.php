<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Production plan header — turns confirmed demand into draft work orders (M09).
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property \Illuminate\Support\Carbon $plan_from_date
 * @property \Illuminate\Support\Carbon $plan_to_date
 * @property int $source_warehouse_id
 * @property int $target_warehouse_id
 * @property DocumentStatus $status
 */
class ProductionPlan extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'plan_from_date',
        'plan_to_date',
        'source_warehouse_id',
        'target_warehouse_id',
        'status',
        'remarks',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'plan_from_date' => 'date',
            'plan_to_date' => 'date',
            'status' => DocumentStatus::class,
            'posted_at' => 'datetime',
        ];
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class)->orderBy('sort_order');
    }

    public function shortages(): HasMany
    {
        return $this->hasMany(ProductionPlanShortage::class);
    }
}
