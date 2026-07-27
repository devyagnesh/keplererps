<?php

namespace App\Models;

use App\Enums\WarehouseLevel;
use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hierarchical warehouse location (Plant → Store → Rack → Bin).
 *
 * @property int $id
 * @property int $branch_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property WarehouseLevel $level
 * @property WarehouseType $warehouse_type
 * @property int $depth
 * @property bool $is_leaf
 * @property bool $allow_negative_stock
 * @property bool $is_system
 * @property bool $is_active
 */
class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'parent_id',
        'code',
        'name',
        'level',
        'warehouse_type',
        'depth',
        'is_leaf',
        'allow_negative_stock',
        'is_system',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'depth' => 1,
        'warehouse_type' => 'store',
        'is_leaf' => true,
        'allow_negative_stock' => false,
        'is_system' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => WarehouseLevel::class,
            'warehouse_type' => WarehouseType::class,
            'depth' => 'integer',
            'is_leaf' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Owning branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Parent warehouse node.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child warehouse nodes.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Whether this node may hold stock (leaf only).
     */
    public function isLeaf(): bool
    {
        return (bool) $this->is_leaf;
    }
}
