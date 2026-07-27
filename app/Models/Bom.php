<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bill of Materials header (M04).
 *
 * @property int $id
 * @property string $bom_number
 * @property int $item_id
 * @property int $version
 * @property string $output_quantity
 * @property int $output_uom_id
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_to
 * @property bool $is_active
 * @property string $overhead_percent
 * @property string|null $notes
 * @property string $rolled_material_cost
 * @property string $rolled_operation_cost
 * @property string $rolled_total_cost
 */
class Bom extends Model
{
    /** @use HasFactory<\Database\Factories\BomFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bom_number',
        'item_id',
        'version',
        'output_quantity',
        'output_uom_id',
        'valid_from',
        'valid_to',
        'is_active',
        'overhead_percent',
        'notes',
        'rolled_material_cost',
        'rolled_operation_cost',
        'rolled_total_cost',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'output_quantity' => 'decimal:4',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
            'overhead_percent' => 'decimal:2',
            'rolled_material_cost' => 'decimal:2',
            'rolled_operation_cost' => 'decimal:2',
            'rolled_total_cost' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function outputUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'output_uom_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(BomComponent::class)->orderBy('sort_order');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class)->orderBy('sequence');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(BomOutput::class);
    }
}
