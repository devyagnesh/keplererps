<?php

namespace App\Models;

use App\Enums\BomIssueMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Work order component requirement snapshot (M09).
 */
class WorkOrderComponent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_order_id',
        'item_id',
        'uom_id',
        'required_quantity',
        'issued_quantity',
        'is_critical',
        'issue_method',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:4',
            'issued_quantity' => 'decimal:4',
            'is_critical' => 'boolean',
            'issue_method' => BomIssueMethod::class,
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function pendingIssueQty(): float
    {
        return max(0, round((float) $this->required_quantity - (float) $this->issued_quantity, 4));
    }
}
