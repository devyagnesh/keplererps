<?php

namespace App\Models;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\QcDisposition;
use App\Enums\SamplingPlanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QC inspection document (M10).
 */
class QcInspection extends Model
{
    /** @use HasFactory<\Database\Factories\QcInspectionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'public_token',
        'inspection_type',
        'status',
        'source_type',
        'source_id',
        'item_id',
        'batch_id',
        'qc_template_id',
        'quarantine_warehouse_id',
        'target_warehouse_id',
        'lot_quantity',
        'sample_size',
        'sampling_plan',
        'sample_override_reason',
        'overall_result',
        'disposition',
        'accepted_qty',
        'rejected_qty',
        'rework_qty',
        'deviation_note',
        'deviation_approved_by',
        'deviation_approved_at',
        'inspector_id',
        'completed_at',
        'remarks',
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
            'inspection_type' => InspectionType::class,
            'status' => InspectionStatus::class,
            'sampling_plan' => SamplingPlanType::class,
            'disposition' => QcDisposition::class,
            'lot_quantity' => 'decimal:4',
            'sample_size' => 'decimal:4',
            'accepted_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'rework_qty' => 'decimal:4',
            'deviation_approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QcTemplate::class, 'qc_template_id');
    }

    public function quarantineWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'quarantine_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(QcInspectionReading::class)->orderBy('sort_order');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
