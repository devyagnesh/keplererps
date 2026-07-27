<?php

namespace App\Models;

use App\Enums\GstType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared customer/supplier (party) master.
 *
 * @property int $id
 * @property string $party_code
 * @property string $party_name
 * @property PartyType $party_type
 * @property GstType $gst_type
 * @property string|null $gstin
 * @property string|null $pan
 * @property string $billing_line1
 * @property string|null $billing_line2
 * @property string $billing_city
 * @property int $billing_state_id
 * @property string $billing_pin_code
 * @property string $billing_country
 * @property string $credit_limit
 * @property bool $unlimited_credit
 * @property int|null $credit_days
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 * @property string|null $bank_ifsc
 * @property string|null $bank_name
 * @property int|null $assigned_user_id
 * @property PartyStatus $status
 * @property bool $has_transactions
 */
class Party extends Model
{
    /** @use HasFactory<\Database\Factories\PartyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'party_code',
        'party_name',
        'party_type',
        'gst_type',
        'gstin',
        'pan',
        'billing_line1',
        'billing_line2',
        'billing_city',
        'billing_state_id',
        'billing_pin_code',
        'billing_country',
        'credit_limit',
        'unlimited_credit',
        'credit_days',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'assigned_user_id',
        'status',
        'has_transactions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'billing_country' => 'India',
        'credit_limit' => 0,
        'unlimited_credit' => false,
        'status' => 'active',
        'has_transactions' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'party_type' => PartyType::class,
            'gst_type' => GstType::class,
            'status' => PartyStatus::class,
            'credit_limit' => 'decimal:2',
            'unlimited_credit' => 'boolean',
            'credit_days' => 'integer',
            'has_transactions' => 'boolean',
        ];
    }

    /**
     * Billing state for GST place-of-supply.
     */
    public function billingState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'billing_state_id');
    }

    /**
     * Assigned sales executive.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Additional addresses.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    /**
     * Contact persons.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }
}
