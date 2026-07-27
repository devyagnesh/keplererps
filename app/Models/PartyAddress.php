<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Additional address for a party (shipping / factory / billing).
 *
 * @property int $id
 * @property int $party_id
 * @property AddressType $address_type
 * @property string|null $label
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property int $state_id
 * @property string $pin_code
 * @property string $country
 * @property bool $is_default
 */
class PartyAddress extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'party_id',
        'address_type',
        'label',
        'line1',
        'line2',
        'city',
        'state_id',
        'pin_code',
        'country',
        'is_default',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'country' => 'India',
        'is_default' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address_type' => AddressType::class,
            'is_default' => 'boolean',
        ];
    }

    /**
     * Parent party.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Address state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
