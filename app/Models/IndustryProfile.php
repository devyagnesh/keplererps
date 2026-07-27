<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Industry configuration pack (feature flags, costing, attributes).
 */
class IndustryProfile extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'modules',
        'uom',
        'costing',
        'item_attributes',
        'qc_templates',
        'reports',
        'print_templates',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'uom' => 'array',
            'costing' => 'array',
            'item_attributes' => 'array',
            'qc_templates' => 'array',
            'reports' => 'array',
            'print_templates' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
