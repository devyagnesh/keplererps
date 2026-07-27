<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Journal voucher header — the only way money enters the general ledger (M13).
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property VoucherType $voucher_type
 * @property DocumentStatus $status
 */
class JournalVoucher extends Model
{
    /** @use HasFactory<\Database\Factories\JournalVoucherFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'financial_year_id',
        'voucher_type',
        'status',
        'reference_no',
        'source_type',
        'source_id',
        'total_debit',
        'total_credit',
        'narration',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'voucher_type' => VoucherType::class,
            'status' => DocumentStatus::class,
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class)->orderBy('sort_order');
    }

    public function source(): MorphTo
    {
        return $this->morphTo(type: 'source_type', id: 'source_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VoucherAllocation::class);
    }
}
