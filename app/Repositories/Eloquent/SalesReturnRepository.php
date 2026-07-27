<?php

namespace App\Repositories\Eloquent;

use App\Enums\DocumentStatus;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\SalesReturnRepositoryInterface;

/**
 * Eloquent sales return repository.
 */
class SalesReturnRepository implements SalesReturnRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): SalesReturn
    {
        return SalesReturn::query()
            ->with([
                'customer:id,party_code,party_name',
                'salesInvoice:id,document_no,document_date,warehouse_id',
                'warehouse:id,code,name',
                'items.item:id,item_code,item_name,tracking_type',
                'items.uom:id,code',
                'items.batch:id,batch_no,expiry_date',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): SalesReturn
    {
        return SalesReturn::query()->create($data);
    }

    public function update(int $id, array $data): SalesReturn
    {
        $record = SalesReturn::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) SalesReturn::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = SalesReturn::query()->with([
            'customer:id,party_code,party_name',
            'salesInvoice:id,document_no',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'customer_id', 'grand_total', 'status', 'created_at'],
            ['document_no', 'reason'],
            function (SalesReturn $return): array {
                return [
                    'id' => $return->id,
                    'document_no' => $return->document_no,
                    'document_date' => $return->document_date?->format('Y-m-d'),
                    'customer' => $return->customer
                        ? $return->customer->party_code.' — '.e($return->customer->party_name)
                        : '—',
                    'sales_invoice' => $return->salesInvoice?->document_no ?? '—',
                    'reason' => e($return->reason),
                    'grand_total' => number_format((float) $return->grand_total, 2, '.', ''),
                    'status' => $return->status->label(),
                    'action' => view('admin.sales-returns.partials.actions', ['return' => $return])->render(),
                ];
            },
            $params
        );
    }

    public function returnedQtyByInvoiceItem(array $salesInvoiceItemIds, ?int $ignoreReturnId = null): array
    {
        if ($salesInvoiceItemIds === []) {
            return [];
        }

        return SalesReturnItem::query()
            ->selectRaw('sales_invoice_item_id, SUM(quantity) as returned_qty')
            ->whereIn('sales_invoice_item_id', $salesInvoiceItemIds)
            ->whereHas('salesReturn', function ($query) use ($ignoreReturnId): void {
                $query->where('status', '!=', DocumentStatus::Cancelled->value);
                if ($ignoreReturnId !== null) {
                    $query->where('id', '!=', $ignoreReturnId);
                }
            })
            ->groupBy('sales_invoice_item_id')
            ->pluck('returned_qty', 'sales_invoice_item_id')
            ->map(fn ($qty): float => round((float) $qty, 4))
            ->all();
    }
}
