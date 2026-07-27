<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BOM co-product / by-product / scrap output line.
 *
 * @property int $id
 * @property int $bom_id
 * @property int $item_id
 * @property string $expected_quantity
 * @property int $uom_id
 * @property string $cost_allocation_percent
 * @property string $output_type
 */
class BomOutput extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'bom_id',
        'item_id',
        'expected_quantity',
        'uom_id',
        'cost_allocation_percent',
        'output_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_quantity' => 'decimal:4',
            'cost_allocation_percent' => 'decimal:2',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
