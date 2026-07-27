<?php

namespace App\Models;

use App\Enums\BomIssueMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BOM component (input material) line.
 *
 * @property int $id
 * @property int $bom_id
 * @property int $component_item_id
 * @property string $quantity
 * @property int $uom_id
 * @property string $wastage_percent
 * @property bool $is_critical
 * @property BomIssueMethod $issue_method
 * @property int|null $operation_sequence
 */
class BomComponent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'bom_id',
        'component_item_id',
        'quantity',
        'uom_id',
        'wastage_percent',
        'is_critical',
        'issue_method',
        'operation_sequence',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'wastage_percent' => 'decimal:2',
            'is_critical' => 'boolean',
            'issue_method' => BomIssueMethod::class,
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
