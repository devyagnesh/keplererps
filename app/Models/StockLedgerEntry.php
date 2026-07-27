<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable stock ledger row (SRS A3 / BR-25).
 *
 * @property int $id
 * @property int $item_id
 * @property int $warehouse_id
 * @property int|null $batch_id
 * @property int|null $serial_id
 * @property StockTransactionType $transaction_type
 * @property \Illuminate\Support\Carbon $posting_at
 * @property string $qty_in
 * @property string $qty_out
 * @property string $rate
 * @property string $value
 * @property string $balance_qty
 * @property string $balance_value
 * @property string $source_type
 * @property int $source_id
 * @property int|null $created_by
 * @property string|null $remarks
 */
class StockLedgerEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'batch_id',
        'serial_id',
        'transaction_type',
        'posting_at',
        'qty_in',
        'qty_out',
        'rate',
        'value',
        'balance_qty',
        'balance_value',
        'source_type',
        'source_id',
        'created_by',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_type' => StockTransactionType::class,
            'posting_at' => 'datetime',
            'qty_in' => 'decimal:4',
            'qty_out' => 'decimal:4',
            'rate' => 'decimal:4',
            'value' => 'decimal:2',
            'balance_qty' => 'decimal:4',
            'balance_value' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
