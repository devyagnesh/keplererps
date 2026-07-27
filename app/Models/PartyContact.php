<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact person for a party.
 *
 * @property int $id
 * @property int $party_id
 * @property string $name
 * @property string $mobile
 * @property string|null $email
 * @property string|null $designation
 * @property bool $whatsapp_opt_in
 * @property \Illuminate\Support\Carbon|null $whatsapp_opt_in_at
 * @property bool $is_primary
 */
class PartyContact extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'party_id',
        'name',
        'mobile',
        'email',
        'designation',
        'whatsapp_opt_in',
        'whatsapp_opt_in_at',
        'is_primary',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'whatsapp_opt_in' => false,
        'is_primary' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'whatsapp_opt_in' => 'boolean',
            'whatsapp_opt_in_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Parent party.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
