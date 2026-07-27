<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Enums\WarehouseType;
use App\Models\Batch;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockLedgerEntry;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock ledger engine — sole writer of inventory quantities (SRS A3, BR-24–BR-26).
 */
class StockLedgerService
{
    /**
     * Post a single ledger movement and refresh the cached balance in one transaction.
     *
     * @param  array{
     *     item_id: int,
     *     warehouse_id: int,
     *     batch_id?: int|null,
     *     serial_id?: int|null,
     *     transaction_type: StockTransactionType|string,
     *     posting_at?: Carbon|string|null,
     *     qty_in?: float|string,
     *     qty_out?: float|string,
     *     rate?: float|string|null,
     *     source: Model,
     *     remarks?: string|null
     * }  $data
     *
     * @throws ValidationException
     */
    public function post(array $data): StockLedgerEntry
    {
        return DB::transaction(function () use ($data): StockLedgerEntry {
            $item = Item::query()->lockForUpdate()->findOrFail((int) $data['item_id']);
            $warehouse = Warehouse::query()->findOrFail((int) $data['warehouse_id']);

            $this->assertStockableItem($item);
            $this->assertLeafWarehouse($warehouse);

            $qtyIn = round((float) ($data['qty_in'] ?? 0), 4);
            $qtyOut = round((float) ($data['qty_out'] ?? 0), 4);
            $this->assertExclusiveQuantities($qtyIn, $qtyOut);

            $batchId = isset($data['batch_id']) ? (int) $data['batch_id'] : null;
            if ($batchId === 0) {
                $batchId = null;
            }
            $this->assertTracking($item, $batchId, $data['serial_id'] ?? null);

            $batchKey = $batchId ?? 0;
            $balance = $this->lockBalance((int) $item->id, (int) $warehouse->id, $batchId, $batchKey);

            $transactionType = $data['transaction_type'] instanceof StockTransactionType
                ? $data['transaction_type']
                : StockTransactionType::from((string) $data['transaction_type']);

            $postingDate = isset($data['posting_at']) ? Carbon::parse($data['posting_at']) : now();

            // Reversals must always be possible, even if the batch has since expired.
            if ($qtyOut > 0 && $batchId !== null && $transactionType !== StockTransactionType::Reversal) {
                $this->assertBatchIssuable($item, $batchId, $postingDate);
            }

            $rate = $data['rate'] ?? null;
            if ($qtyOut > 0) {
                $rate = $this->resolveOutwardRate($balance, $rate);
            } else {
                $rate = round((float) ($rate ?? 0), 4);
                if ($rate < 0) {
                    throw ValidationException::withMessages(['rate' => 'Rate cannot be negative.']);
                }
            }

            $value = round(($qtyIn > 0 ? $qtyIn : $qtyOut) * $rate, 2);
            $newQty = round((float) $balance->qty + $qtyIn - $qtyOut, 4);
            $newValue = round((float) $balance->value + ($qtyIn > 0 ? $value : -$value), 2);

            if ($newQty < -0.00005 && ! $warehouse->allow_negative_stock) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Insufficient stock for %s in %s. Available: %s.',
                        $item->item_code,
                        $warehouse->name,
                        number_format((float) $balance->qty, 4, '.', '')
                    ),
                ]);
            }

            if ($newQty <= 0.00005) {
                $newQty = 0;
                $newValue = 0;
            }

            /** @var Model $source */
            $source = $data['source'];
            $entry = StockLedgerEntry::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'batch_id' => $batchId,
                'serial_id' => $data['serial_id'] ?? null,
                'transaction_type' => $transactionType,
                'posting_at' => $postingDate,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'rate' => $rate,
                'value' => $value,
                'balance_qty' => $newQty,
                'balance_value' => $newValue,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'created_by' => Auth::id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $balance->forceFill([
                'qty' => $newQty,
                'value' => $newValue,
            ])->save();

            if (! $item->has_stock || ! $item->has_transactions) {
                $item->forceFill([
                    'has_stock' => true,
                    'has_transactions' => true,
                ])->save();
            }

            return $entry;
        });
    }

    /**
     * Reverse all ledger rows for a source document (BR-25).
     *
     * @return list<StockLedgerEntry>
     */
    public function reverseSource(Model $source, ?string $remarks = null): array
    {
        return DB::transaction(function () use ($source, $remarks): array {
            $entries = StockLedgerEntry::query()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->where('transaction_type', '!=', StockTransactionType::Reversal->value)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $reversals = [];
            foreach ($entries as $entry) {
                $reversals[] = $this->post([
                    'item_id' => $entry->item_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'batch_id' => $entry->batch_id,
                    'serial_id' => $entry->serial_id,
                    'transaction_type' => StockTransactionType::Reversal,
                    'posting_at' => now(),
                    'qty_in' => (float) $entry->qty_out,
                    'qty_out' => (float) $entry->qty_in,
                    'rate' => (float) $entry->rate,
                    'source' => $source,
                    'remarks' => $remarks ?? 'Reversal of ledger #'.$entry->id,
                ]);
            }

            return $reversals;
        });
    }

    /**
     * Allocate an outbound quantity across batches using FEFO (BR-27).
     *
     * Batches already expired on the posting date are skipped for expiry-tracked items,
     * and batches without an expiry date are consumed last.
     *
     * @return list<array{batch_id: int, quantity: float}>
     *
     * @throws ValidationException
     */
    public function allocateFefo(int $itemId, int $warehouseId, float $qty, Carbon|string|null $asOf = null): array
    {
        $required = round($qty, 4);
        if ($required <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Allocation quantity must be greater than zero.']);
        }

        $item = Item::query()->findOrFail($itemId);
        $asOfDate = ($asOf === null ? now() : Carbon::parse($asOf))->copy()->startOfDay();

        $candidates = StockBalance::query()
            ->with('batch')
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('batch_id')
            ->where('qty', '>', 0)
            ->get()
            ->filter(fn (StockBalance $balance): bool => $balance->batch !== null
                && $balance->batch->is_active
                && ! $this->batchIsExpired($item, $balance->batch, $asOfDate))
            // Earliest expiry first; undated batches last, then oldest batch id as tie-break.
            ->sortBy(fn (StockBalance $balance): array => [
                $balance->batch->expiry_date === null ? 1 : 0,
                $balance->batch->expiry_date?->getTimestamp() ?? 0,
                (int) $balance->batch_id,
            ])
            ->values();

        $allocations = [];
        $remaining = $required;

        foreach ($candidates as $balance) {
            if ($remaining <= 0.00005) {
                break;
            }

            $take = min($remaining, round((float) $balance->qty, 4));
            if ($take <= 0) {
                continue;
            }

            $allocations[] = [
                'batch_id' => (int) $balance->batch_id,
                'quantity' => round($take, 4),
            ];
            $remaining = round($remaining - $take, 4);
        }

        if ($remaining > 0.00005) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Insufficient unexpired batch stock for %s. Short by %s.',
                    $item->item_code,
                    number_format($remaining, 4, '.', '')
                ),
            ]);
        }

        return $allocations;
    }

    /**
     * Whether a batch is past its expiry date for an expiry-tracked item.
     */
    public function batchIsExpired(Item $item, Batch $batch, ?Carbon $asOf = null): bool
    {
        if (! $item->expiry_tracking || $batch->expiry_date === null) {
            return false;
        }

        return $batch->expiry_date->lt(($asOf ?? now())->copy()->startOfDay());
    }

    /**
     * Guard an outbound batch movement: the batch must belong to the item, be active,
     * and not be expired on the posting date (BR-27).
     *
     * @throws ValidationException
     */
    protected function assertBatchIssuable(Item $item, int $batchId, Carbon $postingAt): void
    {
        $batch = Batch::query()->find($batchId);

        if ($batch === null || (int) $batch->item_id !== (int) $item->id) {
            throw ValidationException::withMessages([
                'batch_id' => 'Selected batch does not belong to '.$item->item_code.'.',
            ]);
        }

        if (! $batch->is_active) {
            throw ValidationException::withMessages([
                'batch_id' => 'Batch '.$batch->batch_no.' is blocked and cannot be issued.',
            ]);
        }

        if ($this->batchIsExpired($item, $batch, $postingAt)) {
            throw ValidationException::withMessages([
                'batch_id' => sprintf(
                    'Batch %s of %s expired on %s and cannot be issued.',
                    $batch->batch_no,
                    $item->item_code,
                    $batch->expiry_date->toDateString()
                ),
            ]);
        }
    }

    /**
     * Average valuation rate for an item/warehouse/batch balance.
     */
    public function averageRate(int $itemId, int $warehouseId, ?int $batchId = null): float
    {
        $batchKey = $batchId ?? 0;
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', $batchKey)
            ->first();

        if ($balance === null || (float) $balance->qty <= 0) {
            return 0.0;
        }

        return round((float) $balance->value / (float) $balance->qty, 4);
    }

    protected function lockBalance(int $itemId, int $warehouseId, ?int $batchId, int $batchKey): StockBalance
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_key', $batchKey)
            ->lockForUpdate()
            ->first();

        if ($balance !== null) {
            return $balance;
        }

        return StockBalance::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'batch_id' => $batchId,
            'batch_key' => $batchKey,
            'qty' => 0,
            'committed_qty' => 0,
            'on_order_qty' => 0,
            'value' => 0,
        ]);
    }

    protected function resolveOutwardRate(StockBalance $balance, mixed $rate): float
    {
        if ($rate !== null && $rate !== '') {
            return round((float) $rate, 4);
        }

        if ((float) $balance->qty <= 0) {
            return 0.0;
        }

        return round((float) $balance->value / (float) $balance->qty, 4);
    }

    protected function assertStockableItem(Item $item): void
    {
        if ($item->item_type === ItemType::Service || ! $item->item_type->isStocked()) {
            throw ValidationException::withMessages([
                'item_id' => 'Service items cannot be stocked.',
            ]);
        }

        if (! $item->is_active) {
            throw ValidationException::withMessages([
                'item_id' => 'Inactive items cannot receive stock movements.',
            ]);
        }
    }

    protected function assertLeafWarehouse(Warehouse $warehouse): void
    {
        if (! $warehouse->is_active) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Inactive warehouses cannot receive stock movements.',
            ]);
        }

        if (! $warehouse->is_leaf) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Stock can only be posted to a leaf warehouse location.',
            ]);
        }
    }

    protected function assertExclusiveQuantities(float $qtyIn, float $qtyOut): void
    {
        if ($qtyIn < 0 || $qtyOut < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity cannot be negative.',
            ]);
        }

        if (($qtyIn > 0 && $qtyOut > 0) || ($qtyIn == 0.0 && $qtyOut == 0.0)) {
            throw ValidationException::withMessages([
                'quantity' => 'Exactly one of quantity in or quantity out must be greater than zero.',
            ]);
        }
    }

    protected function assertTracking(Item $item, ?int $batchId, mixed $serialId): void
    {
        $tracking = $item->tracking_type;

        if (in_array($tracking, [TrackingType::Batch, TrackingType::BatchSerial], true) && $batchId === null) {
            throw ValidationException::withMessages([
                'batch_id' => 'Batch is required for batch-tracked items.',
            ]);
        }

        if (in_array($tracking, [TrackingType::Serial, TrackingType::BatchSerial], true) && empty($serialId)) {
            throw ValidationException::withMessages([
                'serial_id' => 'Serial is required for serial-tracked items.',
            ]);
        }
    }

    /**
     * Whether warehouse stock should count toward free/available quantity.
     */
    public function warehouseCountsAsAvailable(Warehouse $warehouse): bool
    {
        $type = $warehouse->warehouse_type instanceof WarehouseType
            ? $warehouse->warehouse_type
            : WarehouseType::from((string) $warehouse->warehouse_type);

        return $type->countsAsAvailable();
    }
}
