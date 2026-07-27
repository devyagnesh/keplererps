<?php

namespace App\Models;

use App\Enums\QcParameterType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QC inspection parameter reading (M10).
 */
class QcInspectionReading extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'qc_inspection_id',
        'qc_template_parameter_id',
        'parameter_name',
        'parameter_type',
        'is_critical',
        'min_value',
        'max_value',
        'target_value',
        'numeric_value',
        'pass_fail_value',
        'text_value',
        'result',
        'defect_reason_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameter_type' => QcParameterType::class,
            'is_critical' => 'boolean',
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
            'target_value' => 'decimal:4',
            'numeric_value' => 'decimal:4',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }
}
