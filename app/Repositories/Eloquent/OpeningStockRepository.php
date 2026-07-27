<?php

namespace App\Repositories\Eloquent;

use App\Models\OpeningStock;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\OpeningStockRepositoryInterface;

/**
 * Eloquent opening stock repository.
 */
class OpeningStockRepository implements OpeningStockRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): OpeningStock
    {
        return OpeningStock::query()
            ->with(['warehouse:id,code,name', 'items.item:id,item_code,item_name,tracking_type'])
            ->findOrFail($id);
    }

    public function create(array $data): OpeningStock
    {
        return OpeningStock::query()->create($data);
    }

    public function update(int $id, array $data): OpeningStock
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh(['warehouse', 'items.item']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function nextDocumentNo(string $prefix = 'OS'): string
    {
        $latest = OpeningStock::query()
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
        $query = OpeningStock::query()->with(['warehouse:id,code,name']);

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'warehouse_id', 'status', 'created_at'],
            ['document_no', 'remarks'],
            function (OpeningStock $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'warehouse' => $doc->warehouse?->name ?? '—',
                    'status' => $doc->status->label(),
                    'action' => view('admin.opening-stocks.partials.actions', ['openingStock' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
