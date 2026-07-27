<?php

namespace App\Repositories\Eloquent;

use App\Models\SalesQuotation;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\SalesQuotationRepositoryInterface;

class SalesQuotationRepository implements SalesQuotationRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): SalesQuotation
    {
        return SalesQuotation::query()
            ->with([
                'customer:id,party_code,party_name,party_type,status,billing_state_id,credit_limit,unlimited_credit',
                'warehouse:id,code,name',
                'placeOfSupplyState:id,code,name',
                'items.item:id,item_code,item_name,selling_price,gst_rate,stock_uom_id,is_sellable',
                'items.uom:id,code,name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): SalesQuotation
    {
        return SalesQuotation::query()->create($data);
    }

    public function update(int $id, array $data): SalesQuotation
    {
        SalesQuotation::query()->findOrFail($id)->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) SalesQuotation::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = SalesQuotation::query()->with(['customer:id,party_code,party_name']);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'customer_id', 'status', 'grand_total', 'created_at'],
            ['document_no'],
            function (SalesQuotation $doc): array {
                return [
                    'id' => $doc->id,
                    'document_no' => $doc->document_no,
                    'document_date' => $doc->document_date?->format('Y-m-d'),
                    'customer' => $doc->customer ? $doc->customer->party_code.' — '.$doc->customer->party_name : '—',
                    'status' => $doc->status->label(),
                    'grand_total' => number_format((float) $doc->grand_total, 2),
                    'action' => view('admin.sales-quotations.partials.actions', ['quotation' => $doc])->render(),
                ];
            },
            $params
        );
    }
}
