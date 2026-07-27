<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\TrackingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Item master (raw materials through finished goods and services).
 *
 * @property int $id
 * @property string $item_code
 * @property string $item_name
 * @property ItemType $item_type
 * @property int $category_id
 * @property int|null $sub_category_id
 * @property int $stock_uom_id
 * @property int|null $purchase_uom_id
 * @property int|null $sales_uom_id
 * @property int $hsn_code_id
 * @property string $gst_rate
 * @property string $cess_rate
 * @property TrackingType $tracking_type
 * @property bool $expiry_tracking
 * @property int|null $shelf_life_days
 * @property string $standard_cost
 * @property string|null $selling_price
 * @property string|null $minimum_selling_price
 * @property string|null $min_stock
 * @property string|null $max_stock
 * @property int|null $lead_time_days
 * @property int|null $default_warehouse_id
 * @property string|null $weight_per_unit
 * @property string|null $barcode
 * @property bool $is_purchasable
 * @property bool $is_sellable
 * @property bool $is_manufacturable
 * @property bool $is_active
 * @property bool $has_transactions
 * @property bool $has_stock
 * @property string|null $description
 */
class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_code',
        'item_name',
        'item_type',
        'category_id',
        'sub_category_id',
        'stock_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'hsn_code_id',
        'gst_rate',
        'cess_rate',
        'tracking_type',
        'expiry_tracking',
        'shelf_life_days',
        'standard_cost',
        'selling_price',
        'piece_rate',
        'minimum_selling_price',
        'min_stock',
        'max_stock',
        'lead_time_days',
        'default_warehouse_id',
        'weight_per_unit',
        'barcode',
        'is_purchasable',
        'is_sellable',
        'is_manufacturable',
        'requires_inspection',
        'is_active',
        'has_transactions',
        'has_stock',
        'description',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tracking_type' => 'none',
        'expiry_tracking' => false,
        'cess_rate' => 0,
        'standard_cost' => 0,
        'is_purchasable' => false,
        'is_sellable' => false,
        'is_manufacturable' => false,
        'requires_inspection' => false,
        'is_active' => true,
        'has_transactions' => false,
        'has_stock' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'tracking_type' => TrackingType::class,
            'expiry_tracking' => 'boolean',
            'gst_rate' => 'decimal:2',
            'cess_rate' => 'decimal:2',
            'standard_cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'piece_rate' => 'decimal:4',
            'minimum_selling_price' => 'decimal:4',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'weight_per_unit' => 'decimal:4',
            'shelf_life_days' => 'integer',
            'lead_time_days' => 'integer',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_manufacturable' => 'boolean',
            'requires_inspection' => 'boolean',
            'is_active' => 'boolean',
            'has_transactions' => 'boolean',
            'has_stock' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function stockUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'stock_uom_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'purchase_uom_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'sales_uom_id');
    }

    public function hsnCode(): BelongsTo
    {
        return $this->belongsTo(HsnCode::class);
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(ItemUomConversion::class);
    }

    public function warehouseSettings(): HasMany
    {
        return $this->hasMany(ItemWarehouseSetting::class);
    }

    public function substitutes(): HasMany
    {
        return $this->hasMany(ItemSubstitute::class);
    }
}
