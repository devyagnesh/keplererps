<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * GST tax rate master.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $cgst_rate
 * @property string $sgst_rate
 * @property string $igst_rate
 * @property string $cess_rate
 * @property bool $is_active
 * @property bool $has_transactions
 */
class TaxRate extends Model
{
    /** @use HasFactory<\Database\Factories\TaxRateFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cess_rate',
        'is_active',
        'has_transactions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cgst_rate' => 0,
        'sgst_rate' => 0,
        'igst_rate' => 0,
        'cess_rate' => 0,
        'is_active' => true,
        'has_transactions' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cgst_rate' => 'decimal:2',
            'sgst_rate' => 'decimal:2',
            'igst_rate' => 'decimal:2',
            'cess_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'has_transactions' => 'boolean',
        ];
    }
}
