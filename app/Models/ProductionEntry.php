<?php

namespace App\Models;

use App\Enums\RejectionDisposition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shop-floor production entry (M09).
 */
class ProductionEntry extends Model
{
    /** @use HasFactory<\Database\Factories\ProductionEntryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'work_order_id',
        'good_quantity',
        'rejected_quantity',
        'defect_reason_id',
        'rejection_disposition',
        'downgrade_item_id',
        'batch_no',
        'batch_id',
        'start_time',
        'end_time',
        'downtime_minutes',
        'downtime_reason',
        'machine_hours',
        'labour_hours',
        'material_cost',
        'machine_cost',
        'labour_cost',
        'overhead_cost',
        'total_cost',
        'operator_user_id',
        'remarks',
        'posted_at',
        'posted_by',
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
            'good_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'rejection_disposition' => RejectionDisposition::class,
            'downtime_minutes' => 'integer',
            'machine_hours' => 'decimal:4',
            'labour_hours' => 'decimal:4',
            'material_cost' => 'decimal:2',
            'machine_cost' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function defectReason(): BelongsTo
    {
        return $this->belongsTo(DefectReason::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionEntryMaterial::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
