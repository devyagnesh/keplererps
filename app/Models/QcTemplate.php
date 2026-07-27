<?php

namespace App\Models;

use App\Enums\InspectionType;
use App\Enums\SamplingPlanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QC inspection template (M10).
 */
class QcTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\QcTemplateFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'inspection_type',
        'item_id',
        'category_id',
        'sampling_plan',
        'sampling_value',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inspection_type' => InspectionType::class,
            'sampling_plan' => SamplingPlanType::class,
            'sampling_value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(QcTemplateParameter::class)->orderBy('sort_order');
    }
}
