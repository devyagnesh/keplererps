<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentSeriesType;
use App\Enums\StockTransactionType;
use App\Enums\TrackingType;
use App\Models\Batch;
use App\Models\OpeningStock;
use App\Models\OpeningStockItem;
use App\Models\Serial;
use App\Repositories\Interfaces\OpeningStockRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opening stock document business logic (M08 Phase 1).
 */
class OpeningStockService
{
    public function __construct(
        protected OpeningStockRepositoryInterface $repository,
        protected StockLedgerService $ledger,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): OpeningStock
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpeningStock
    {
        return DB::transaction(function () use ($data): OpeningStock {
            $lines = $data['items'] ?? [];
            unset($data['items']);

            $data['document_no'] = $this->numbering->next(DocumentSeriesType::OpeningStock);
            $data['status'] = DocumentStatus::Draft->value;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $doc = $this->repository->create($data);
            $this->syncItems($doc, $lines);

            return $doc->fresh(['warehouse', 'items.item']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): OpeningStock
    {
        return DB::transaction(function () use ($id, $data): OpeningStock {
            $doc = $this->repository->findById($id);
            $this->assertDraft($doc);

            $lines = $data['items'] ?? [];
            unset($data['items'], $data['document_no'], $data['status']);
            $data['updated_by'] = Auth::id();

            $updated = $this->repository->update($id, $data);
            $this->syncItems($updated, $lines);

            return $updated->fresh(['warehouse', 'items.item']);
        });
    }

    public function delete(int $id): bool
    {
        $doc = $this->repository->findById($id);
        $this->assertDraft($doc);

        return $this->repository->delete($id);
    }

    public function post(int $id): OpeningStock
    {
        return DB::transaction(function () use ($id): OpeningStock {
            $doc = OpeningStock::query()->with('items.item')->lockForUpdate()->findOrFail($id);
            $this->assertDraft($doc);

            if ($doc->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one line before posting.',
                ]);
            }

            foreach ($doc->items as $line) {
                $batchId = $this->resolveBatchId($line);
                $serialId = $this->resolveSerialId($line, $batchId);
                $this->ledger->post([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $doc->warehouse_id,
                    'batch_id' => $batchId,
                    'serial_id' => $serialId,
                    'transaction_type' => StockTransactionType::OpeningStock,
                    'posting_at' => $doc->document_date->copy()->startOfDay(),
                    'qty_in' => (float) $line->quantity,
                    'qty_out' => 0,
                    'rate' => (float) $line->rate,
                    'source' => $doc,
                    'remarks' => $doc->document_no,
                ]);

                $updates = [];
                if ($batchId !== null && $line->batch_id === null) {
                    $updates['batch_id'] = $batchId;
                }
                if ($updates !== []) {
                    $line->forceFill($updates)->save();
                }
            }

            $doc->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            return $doc->fresh(['warehouse', 'items.item']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncItems(OpeningStock $doc, array $lines): void
    {
        $doc->items()->delete();

        foreach ($lines as $line) {
            if (empty($line['item_id']) || empty($line['quantity'])) {
                continue;
            }

            $qty = round((float) $line['quantity'], 4);
            $rate = round((float) ($line['rate'] ?? 0), 4);

            OpeningStockItem::query()->create([
                'opening_stock_id' => $doc->id,
                'item_id' => (int) $line['item_id'],
                'batch_id' => ! empty($line['batch_id']) ? (int) $line['batch_id'] : null,
                'batch_no' => $line['batch_no'] ?? null,
                'mfg_date' => $line['mfg_date'] ?? null,
                'expiry_date' => $line['expiry_date'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'quantity' => $qty,
                'rate' => $rate,
                'value' => round($qty * $rate, 2),
            ]);
        }
    }

    protected function resolveBatchId(OpeningStockItem $line): ?int
    {
        $item = $line->item;
        $tracking = $item->tracking_type;

        if (! in_array($tracking, [TrackingType::Batch, TrackingType::BatchSerial], true)) {
            return $line->batch_id;
        }

        if ($line->batch_id) {
            return (int) $line->batch_id;
        }

        $batchNo = trim((string) ($line->batch_no ?? ''));
        if ($batchNo === '') {
            throw ValidationException::withMessages([
                'items' => "Batch number is required for item {$item->item_code}.",
            ]);
        }

        $batch = Batch::query()->firstOrCreate(
            ['item_id' => $item->id, 'batch_no' => $batchNo],
            [
                'mfg_date' => $line->mfg_date,
                'expiry_date' => $line->expiry_date,
                'is_active' => true,
            ]
        );

        return (int) $batch->id;
    }

    protected function resolveSerialId(OpeningStockItem $line, ?int $batchId): ?int
    {
        $item = $line->item;
        $tracking = $item->tracking_type;

        if (! in_array($tracking, [TrackingType::Serial, TrackingType::BatchSerial], true)) {
            return null;
        }

        $serialNo = trim((string) ($line->serial_no ?? ''));
        if ($serialNo === '') {
            throw ValidationException::withMessages([
                'items' => "Serial number is required for item {$item->item_code}.",
            ]);
        }

        if ((float) $line->quantity != 1.0) {
            throw ValidationException::withMessages([
                'items' => "Serial-tracked item {$item->item_code} must be posted with quantity 1 per line.",
            ]);
        }

        $serial = Serial::query()->firstOrCreate(
            ['item_id' => $item->id, 'serial_no' => $serialNo],
            [
                'batch_id' => $batchId,
                'status' => 'in_stock',
                'is_active' => true,
            ]
        );

        return (int) $serial->id;
    }

    protected function assertDraft(OpeningStock $doc): void
    {
        if ($doc->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Only draft opening stock documents can be modified.',
            ]);
        }
    }
}
