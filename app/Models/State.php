<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Indian state master used for GST place-of-supply logic.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $tin
 * @property bool $is_union_territory
 * @property bool $is_active
 */
class State extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'tin',
        'is_union_territory',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_union_territory' => false,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_union_territory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Parties billed in this state.
     */
    public function parties(): HasMany
    {
        return $this->hasMany(Party::class, 'billing_state_id');
    }
}
