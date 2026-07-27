<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical, scannable package produced while packing a dispatch (M17).
 *
 * @property int $id
 * @property string $label_no
 * @property string $qr_payload
 * @property PackageStatus $status
 * @property string $quantity
 */
class PackageLabel extends Model
{
    /** @use HasFactory<\Database\Factories\PackageLabelFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'label_no',
        'qr_payload',
        'packing_unit_id',
        'parent_package_label_id',
        'item_id',
        'batch_id',
        'warehouse_id',
        'delivery_challan_id',
        'delivery_challan_item_id',
        'quantity',
        'secondary_quantity',
        'status',
        'packed_at',
        'packed_by',
        'verified_at',
        'verified_by',
        'dispatched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'secondary_quantity' => 'decimal:4',
            'status' => PackageStatus::class,
            'packed_at' => 'datetime',
            'verified_at' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }

    public function packingUnit(): BelongsTo
    {
        return $this->belongsTo(PackingUnit::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PackageLabel::class, 'parent_package_label_id');
    }

    /**
     * @return HasMany<PackageLabel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(PackageLabel::class, 'parent_package_label_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function deliveryChallan(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class);
    }

    public function deliveryChallanItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallanItem::class);
    }
}
