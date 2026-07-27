<?php

namespace App\Repositories\Interfaces;

use App\Models\JournalVoucher;

/**
 * Journal voucher data-access contract.
 */
interface JournalVoucherRepositoryInterface
{
    public function findById(int $id): JournalVoucher;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JournalVoucher;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): JournalVoucher;

    public function delete(int $id): bool;

    /**
     * The voucher auto-posted from a source document, if any.
     */
    public function findForSource(string $sourceType, int $sourceId): ?JournalVoucher;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
