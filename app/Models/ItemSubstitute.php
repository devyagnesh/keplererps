<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alternative raw material / substitute mapping for an item.
 *
 * @property int $id
 * @property int $item_id
 * @property int $substitute_item_id
 * @property string $conversion_ratio
 * @property bool $is_active
 */
class ItemSubstitute extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'substitute_item_id',
        'conversion_ratio',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'conversion_ratio' => 1,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_ratio' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function substituteItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'substitute_item_id');
    }
}
