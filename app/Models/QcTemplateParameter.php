<?php

namespace App\Models;

use App\Enums\QcParameterType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QC template parameter definition (M10).
 */
class QcTemplateParameter extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'qc_template_id',
        'name',
        'parameter_type',
        'uom',
        'min_value',
        'max_value',
        'target_value',
        'is_critical',
        'test_method',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameter_type' => QcParameterType::class,
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
            'target_value' => 'decimal:4',
            'is_critical' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QcTemplate::class, 'qc_template_id');
    }
}
