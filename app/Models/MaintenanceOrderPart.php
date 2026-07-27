<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Spare part line on a maintenance order (US-M11-04).
 */
class MaintenanceOrderPart extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'maintenance_order_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'rate',
        'amount',
        'issued',
        'issued_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'issued' => 'boolean',
            'issued_at' => 'datetime',
        ];
    }

    public function maintenanceOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
