<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit of measure master.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $uom_type
 * @property int $decimal_places
 * @property bool $is_active
 * @property bool $has_transactions
 */
class Uom extends Model
{
    /** @use HasFactory<\Database\Factories\UomFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'uom_type',
        'decimal_places',
        'is_active',
        'has_transactions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'uom_type' => 'count',
        'decimal_places' => 3,
        'is_active' => true,
        'has_transactions' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'has_transactions' => 'boolean',
        ];
    }
}
