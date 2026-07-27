<?php

namespace App\Models;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceOrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Preventive or breakdown maintenance order (M11).
 */
class MaintenanceOrder extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceOrderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'order_type',
        'status',
        'work_centre_id',
        'opened_at',
        'closed_at',
        'reason',
        'action_taken',
        'downtime_minutes',
        'downtime_cost',
        'reported_by',
        'assigned_to',
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
            'order_type' => MaintenanceOrderType::class,
            'status' => MaintenanceOrderStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'downtime_cost' => 'decimal:2',
        ];
    }

    public function workCentre(): BelongsTo
    {
        return $this->belongsTo(WorkCentre::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(MaintenanceOrderPart::class)->orderBy('sort_order');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
