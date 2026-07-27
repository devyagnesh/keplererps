<?php

namespace App\Models;

use App\Enums\DocumentSeriesType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Document number series configuration (SRS C2).
 *
 * @property int $id
 * @property DocumentSeriesType $document_type
 * @property int|null $financial_year_id
 * @property int|null $branch_id
 * @property string $prefix
 * @property string|null $suffix
 * @property string $separator
 * @property int $padding
 * @property int $start_number
 * @property int $next_number
 * @property bool $include_fy_code
 * @property bool $reset_yearly
 * @property bool $is_active
 */
class DocumentNumberSeries extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentNumberSeriesFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'financial_year_id',
        'branch_id',
        'prefix',
        'suffix',
        'separator',
        'padding',
        'start_number',
        'next_number',
        'include_fy_code',
        'reset_yearly',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'separator' => '-',
        'padding' => 5,
        'start_number' => 1,
        'next_number' => 1,
        'include_fy_code' => false,
        'reset_yearly' => true,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentSeriesType::class,
            'padding' => 'integer',
            'start_number' => 'integer',
            'next_number' => 'integer',
            'include_fy_code' => 'boolean',
            'reset_yearly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Format a sequence number without consuming it.
     */
    public function formatNumber(int $sequence, ?string $fyCode = null): string
    {
        $parts = [strtoupper(trim($this->prefix))];

        if ($this->include_fy_code && $fyCode) {
            $parts[] = $fyCode;
        }

        $parts[] = str_pad((string) $sequence, max(1, (int) $this->padding), '0', STR_PAD_LEFT);

        if ($this->suffix) {
            $parts[] = trim((string) $this->suffix);
        }

        return implode($this->separator ?: '-', $parts);
    }
}
