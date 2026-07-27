<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inventory batch / lot for tracked items.
 *
 * @property int $id
 * @property int $item_id
 * @property string $batch_no
 * @property \Illuminate\Support\Carbon|null $mfg_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $supplier_batch_no
 * @property int|null $parent_batch_id
 * @property bool $is_active
 */
class Batch extends Model
{
    /** @use HasFactory<\Database\Factories\BatchFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'batch_no',
        'mfg_date',
        'expiry_date',
        'supplier_batch_no',
        'parent_batch_id',
        'is_active',
        'recalled_at',
        'recall_reason',
        'recalled_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mfg_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
            'recalled_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_batch_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_batch_id');
    }
}
