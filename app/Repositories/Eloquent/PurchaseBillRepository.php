<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseBill;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\PurchaseBillRepositoryInterface;

/**
 * Eloquent purchase bill repository.
 */
class PurchaseBillRepository implements PurchaseBillRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): PurchaseBill
    {
        return PurchaseBill::query()
            ->with([
                'supplier:id,party_code,party_name,gstin',
                'purchaseOrder:id,document_no,document_date',
                'goodsReceipt:id,document_no,document_date',
                'items.item:id,item_code,item_name',
                'items.uom:id,code',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): PurchaseBill
    {
        return PurchaseBill::query()->create($data);
    }

    public function update(int $id, array $data): PurchaseBill
    {
        $record = PurchaseBill::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) PurchaseBill::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = PurchaseBill::query()->with([
            'supplier:id,party_code,party_name',
            'purchaseOrder:id,document_no',
            'goodsReceipt:id,document_no',
        ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['match_status'])) {
            $query->where('match_status', $params['match_status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'supplier_id', 'grand_total', 'status', 'match_status', 'created_at'],
            ['document_no', 'supplier_bill_no'],
            function (PurchaseBill $bill): array {
                return [
                    'id' => $bill->id,
                    'document_no' => $bill->document_no,
                    'document_date' => $bill->document_date?->format('Y-m-d'),
                    'supplier' => $bill->supplier
                        ? $bill->supplier->party_code.' — '.e($bill->supplier->party_name)
                        : '—',
                    'supplier_bill_no' => e($bill->supplier_bill_no),
                    'goods_receipt' => $bill->goodsReceipt?->document_no ?? '—',
                    'grand_total' => number_format((float) $bill->grand_total, 2, '.', ''),
                    'match_status' => $bill->match_status->label(),
                    'status' => $bill->status->label(),
                    'action' => view('admin.purchase-bills.partials.actions', ['bill' => $bill])->render(),
                ];
            },
            $params
        );
    }
}
