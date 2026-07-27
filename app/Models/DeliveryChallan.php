<?php

namespace App\Models;

use App\Enums\DeliveryChallanStatus;
use App\Enums\TransportMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Delivery challan header (M12).
 */
class DeliveryChallan extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryChallanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'status',
        'transport_mode',
        'vehicle_number',
        'transporter_id',
        'transporter_gstin',
        'lr_number',
        'lr_date',
        'distance_km',
        'driver_name',
        'driver_mobile',
        'number_of_packages',
        'gross_weight',
        'net_weight',
        'eway_bill_number',
        'eway_required',
        'dispatch_value',
        'expected_delivery_date',
        'dispatched_at',
        'dispatched_by',
        'delivered_at',
        'pod_path',
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
            'lr_date' => 'date',
            'expected_delivery_date' => 'date',
            'status' => DeliveryChallanStatus::class,
            'transport_mode' => TransportMode::class,
            'number_of_packages' => 'integer',
            'distance_km' => 'integer',
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'eway_required' => 'boolean',
            'dispatch_value' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryChallanItem::class)->orderBy('sort_order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
}
