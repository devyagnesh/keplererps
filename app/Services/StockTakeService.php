<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Models\PackageLabel;
use App\Models\StockBalance;
use App\Models\StockTake;
use App\Models\StockTakeLine;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Physical stock-take with optional package-label scan (M08 / M17).
 */
class StockTakeService
{
    public function __construct(
        protected NumberingService $numbering,
        protected StockAdjustmentService $adjustments
    ) {}

    public function find(int $id): StockTake
    {
        return StockTake::query()->with(['warehouse', 'lines.item', 'lines.batch'])->findOrFail($id);
    }

    /**
     * @param  array{warehouse_id: int, document_date?: string, remarks?: string|null}  $data
     */
    public function create(array $data): StockTake
    {
        $warehouse = Warehouse::query()->findOrFail((int) $data['warehouse_id']);
        if (! $warehouse->is_leaf) {
            throw ValidationException::withMessages(['warehouse_id' => 'Count only at a leaf warehouse.']);
        }

        return StockTake::query()->create([
            'document_no' => $this->numbering->next(DocumentSeriesType::StockAdjustment),
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'document_date' => $data['document_date'] ?? now()->toDateString(),
            'remarks' => $data['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Seed lines from current stock balances for the warehouse.
     */
    public function seedFromBalances(int $id): StockTake
    {
        $take = $this->find($id);
        $this->assertDraft($take);

        $balances = StockBalance::query()
            ->where('warehouse_id', $take->warehouse_id)
            ->where('qty', '!=', 0)
            ->get(['item_id', 'batch_id', 'qty']);

        foreach ($balances as $balance) {
            StockTakeLine::query()->updateOrCreate(
                [
                    'stock_take_id' => $take->id,
                    'item_id' => $balance->item_id,
                    'batch_id' => $balance->batch_id,
                ],
                [
                    'system_qty' => (float) $balance->qty,
                    'counted_qty' => (float) $balance->qty,
                    'variance_qty' => 0,
                ]
            );
        }

        return $this->find($id);
    }

    /**
     * @param  list<array{id?: int, item_id: int, batch_id?: int|null, counted_qty: float, scanned_code?: string|null}>  $lines
     */
    public function saveLines(int $id, array $lines): StockTake
    {
        $take = $this->find($id);
        $this->assertDraft($take);

        return DB::transaction(function () use ($take, $lines, $id): StockTake {
            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $batchId = $line['batch_id'] ?? null;
                $system = (float) StockBalance::query()
                    ->where('warehouse_id', $take->warehouse_id)
                    ->where('item_id', $itemId)
                    ->when($batchId, fn ($q) => $q->where('batch_id', $batchId), fn ($q) => $q->whereNull('batch_id'))
                    ->value('qty');
                $counted = round((float) $line['counted_qty'], 4);

                StockTakeLine::query()->updateOrCreate(
                    [
                        'stock_take_id' => $take->id,
                        'item_id' => $itemId,
                        'batch_id' => $batchId,
                    ],
                    [
                        'system_qty' => $system,
                        'counted_qty' => $counted,
                        'variance_qty' => round($counted - $system, 4),
                        'scanned_code' => $line['scanned_code'] ?? null,
                    ]
                );
            }

            return $this->find($id);
        });
    }

    /**
     * Scan a package label into the count sheet.
     */
    public function scanPackage(int $id, string $code): StockTake
    {
        $take = $this->find($id);
        $this->assertDraft($take);

        $package = PackageLabel::query()
            ->where('label_no', $code)
            ->orWhere('qr_payload', $code)
            ->first();

        if ($package === null) {
            throw ValidationException::withMessages(['code' => 'Package label not found.']);
        }

        return $this->saveLines($id, [[
            'item_id' => $package->item_id,
            'batch_id' => $package->batch_id,
            'counted_qty' => (float) $package->quantity,
            'scanned_code' => $code,
        ]]);
    }

    /**
     * Post variances as a stock adjustment.
     */
    public function post(int $id): StockTake
    {
        $take = $this->find($id);
        $this->assertDraft($take);

        $varianceLines = $take->lines->filter(fn (StockTakeLine $line): bool => abs((float) $line->variance_qty) > 0.0001);
        if ($varianceLines->isEmpty()) {
            throw ValidationException::withMessages(['stock_take' => 'No variances to post.']);
        }

        return DB::transaction(function () use ($take, $varianceLines): StockTake {
            $items = [];
            foreach ($varianceLines as $line) {
                $items[] = [
                    'item_id' => $line->item_id,
                    'batch_id' => $line->batch_id,
                    'quantity' => abs((float) $line->variance_qty),
                    'direction' => (float) $line->variance_qty >= 0 ? 'increase' : 'decrease',
                    'remarks' => 'Stock take '.$take->document_no,
                ];
            }

            $adjustment = $this->adjustments->create([
                'warehouse_id' => $take->warehouse_id,
                'document_date' => $take->document_date->toDateString(),
                'reason' => 'Physical stock take '.$take->document_no,
                'items' => $items,
            ]);
            $this->adjustments->post($adjustment->id);

            $take->forceFill([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ])->save();

            return $this->find($take->id);
        });
    }

    protected function assertDraft(StockTake $take): void
    {
        if ($take->status !== 'draft') {
            throw ValidationException::withMessages(['stock_take' => 'Only draft stock takes can be changed.']);
        }
    }
}
