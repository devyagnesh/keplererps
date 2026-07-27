<?php

namespace App\Repositories\Eloquent;

use App\Models\JournalVoucher;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\JournalVoucherRepositoryInterface;

/**
 * Eloquent journal voucher repository.
 */
class JournalVoucherRepository implements JournalVoucherRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): JournalVoucher
    {
        return JournalVoucher::query()
            ->with([
                'financialYear:id,code',
                'lines.ledgerAccount:id,code,name,account_type',
                'lines.party:id,party_code,party_name',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): JournalVoucher
    {
        return JournalVoucher::query()->create($data);
    }

    public function update(int $id, array $data): JournalVoucher
    {
        $record = JournalVoucher::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) JournalVoucher::query()->findOrFail($id)->delete();
    }

    public function findForSource(string $sourceType, int $sourceId): ?JournalVoucher
    {
        return JournalVoucher::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    public function getForDataTable(array $params): array
    {
        $query = JournalVoucher::query();

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['voucher_type'])) {
            $query->where('voucher_type', $params['voucher_type']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('document_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('document_date', '<=', $params['date_to']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'document_no', 'document_date', 'voucher_type', 'total_debit', 'status', 'created_at'],
            ['document_no', 'reference_no', 'narration'],
            function (JournalVoucher $voucher): array {
                return [
                    'id' => $voucher->id,
                    'document_no' => $voucher->document_no,
                    'document_date' => $voucher->document_date?->format('Y-m-d'),
                    'voucher_type' => $voucher->voucher_type->label(),
                    'reference_no' => e($voucher->reference_no ?? '—'),
                    'amount' => number_format((float) $voucher->total_debit, 2, '.', ''),
                    'status' => $voucher->status->label(),
                    'action' => view('admin.journal-vouchers.partials.actions', ['voucher' => $voucher])->render(),
                ];
            },
            $params
        );
    }
}
