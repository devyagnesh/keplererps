<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Opening stock document header (go-live inventory load).
 *
 * @property int $id
 * @property string $document_no
 * @property \Illuminate\Support\Carbon $document_date
 * @property int $warehouse_id
 * @property DocumentStatus $status
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property int|null $posted_by
 * @property string|null $remarks
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class OpeningStock extends Model
{
    /** @use HasFactory<\Database\Factories\OpeningStockFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_no',
        'document_date',
        'warehouse_id',
        'status',
        'posted_at',
        'posted_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'posted_at' => 'datetime',
            'status' => DocumentStatus::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OpeningStockItem::class);
    }

    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(StockLedgerEntry::class, 'source');
    }
}
