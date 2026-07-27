<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HSN / SAC code master with default GST rate.
 *
 * @property int $id
 * @property string $code
 * @property string $code_type
 * @property string $description
 * @property string $default_gst_rate
 * @property bool $is_active
 */
class HsnCode extends Model
{
    /** @use HasFactory<\Database\Factories\HsnCodeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'code_type',
        'description',
        'default_gst_rate',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'code_type' => 'hsn',
        'default_gst_rate' => 18,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_gst_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Items using this HSN/SAC.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
