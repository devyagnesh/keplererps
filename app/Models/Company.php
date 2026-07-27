<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Singleton company profile used on all printed documents.
 *
 * @property int $id
 * @property string $legal_name
 * @property string|null $trade_name
 * @property bool $is_gst_registered
 * @property string|null $gstin
 * @property string $pan
 * @property string|null $cin
 * @property string $registered_address
 * @property int $state_id
 * @property string $pin_code
 * @property string $phone
 * @property string $email
 * @property string|null $logo_path
 * @property int $fy_start_month
 * @property int $fy_start_day
 * @property string $base_currency
 * @property int $amount_decimals
 * @property int $quantity_decimals
 * @property bool $has_transactions
 */
class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'trade_name',
        'is_gst_registered',
        'gstin',
        'pan',
        'cin',
        'registered_address',
        'state_id',
        'pin_code',
        'phone',
        'email',
        'logo_path',
        'fy_start_month',
        'fy_start_day',
        'base_currency',
        'amount_decimals',
        'quantity_decimals',
        'has_transactions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_gst_registered' => false,
        'fy_start_month' => 4,
        'fy_start_day' => 1,
        'base_currency' => 'INR',
        'amount_decimals' => 2,
        'quantity_decimals' => 3,
        'has_transactions' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_gst_registered' => 'boolean',
            'has_transactions' => 'boolean',
            'fy_start_month' => 'integer',
            'fy_start_day' => 'integer',
            'amount_decimals' => 'integer',
            'quantity_decimals' => 'integer',
        ];
    }

    /**
     * Company registered state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
