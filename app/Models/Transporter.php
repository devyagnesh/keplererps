<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Logistics transporter master.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $gstin
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $vehicle_types
 * @property bool $is_active
 * @property bool $has_transactions
 */
class Transporter extends Model
{
    /** @use HasFactory<\Database\Factories\TransporterFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'gstin',
        'phone',
        'email',
        'address',
        'vehicle_types',
        'is_active',
        'has_transactions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'has_transactions' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_transactions' => 'boolean',
        ];
    }
}
