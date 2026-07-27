<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configurable custom field definition (M16 / C1).
 */
class CustomFieldDefinition extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_type',
        'field_key',
        'label',
        'field_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
