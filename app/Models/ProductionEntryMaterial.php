<?php

namespace App\Models;

use App\Enums\BomIssueMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material consumed on a production entry (M09).
 */
class ProductionEntryMaterial extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_entry_id',
        'work_order_component_id',
        'item_id',
        'uom_id',
        'quantity',
        'rate',
        'value',
        'issue_method',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'value' => 'decimal:2',
            'issue_method' => BomIssueMethod::class,
        ];
    }

    public function productionEntry(): BelongsTo
    {
        return $this->belongsTo(ProductionEntry::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
