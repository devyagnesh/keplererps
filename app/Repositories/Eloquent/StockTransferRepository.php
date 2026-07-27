<?php

namespace App\Repositories\Eloquent;

use App\Models\StockTransfer;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\StockTransferRepositoryInterface;

/**
 * Eloquent stock transfer repository.
 */
class StockTransferRepository implements StockTransferRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): StockTransfer
    {
        return StockTransfer::query()
            ->with([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
                'items.item:id,item_code,item_name,tracking_type',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): StockTransfer
    {
        return StockTransfer::query()->create($data);
    }

    public function update(int $id, array $data): StockTransfer
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh(['fromWarehouse', 'toWarehouse', 'items.item']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function nextDocumentNo(string $prefix = 'TRF'): string
    {
        $latest = StockTransfer::query()
            ->withTrashed()
            ->where('document_no', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('document_no');

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            StockTransfer::query()->with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name']),
            ['id', 'document_no', 'document_date', 'from_warehouse_id', 'to_warehouse_id', 'status', 'created_at'],
            ['document_no', 'remarks'],
            function (StockTransfer $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'from_warehouse' => $doc->fromWarehouse?->name ?? '—',
                    'to_warehouse' => $doc->toWarehouse?->name ?? '—',
                    'status' => $doc->status->label(),
                    'action' => view('admin.stock-transfers.partials.actions', ['stockTransfer' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
