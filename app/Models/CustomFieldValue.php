<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stored value for a custom field on an entity (M16 / C1).
 */
class CustomFieldValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'custom_field_definition_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id');
    }
}
