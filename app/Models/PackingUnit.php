<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Packing unit master with nesting, e.g. a carton holding boxes of pieces (M17).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $item_id
 * @property int|null $parent_id
 * @property string $quantity
 * @property bool $is_active
 */
class PackingUnit extends Model
{
    /** @use HasFactory<\Database\Factories\PackingUnitFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Guard against a corrupt self-referencing chain when walking parents.
     */
    public const MAX_NESTING_DEPTH = 10;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'item_id',
        'parent_id',
        'uom_id',
        'quantity',
        'is_active',
        'remarks',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PackageLabel::class);
    }

    /**
     * Contents of one unit expressed in the base UOM, multiplying every nesting level.
     *
     * A carton of 5 boxes, each holding 50 pieces, resolves to 250 pieces.
     */
    public function baseQuantity(): float
    {
        $quantity = (float) $this->quantity;
        $parent = $this->parent;
        $depth = 0;

        while ($parent !== null && $depth < self::MAX_NESTING_DEPTH) {
            $quantity *= (float) $parent->quantity;
            $parent = $parent->parent;
            $depth++;
        }

        return round($quantity, 4);
    }

    /**
     * Human-readable nesting path, e.g. "Carton › Box".
     */
    public function nestingPath(): string
    {
        $names = [$this->name];
        $parent = $this->parent;
        $depth = 0;

        while ($parent !== null && $depth < self::MAX_NESTING_DEPTH) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
            $depth++;
        }

        return implode(' › ', $names);
    }
}
