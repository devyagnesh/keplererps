<?php

namespace App\Repositories\Interfaces;

use App\Models\DeliveryChallan;

/**
 * Delivery challan data-access contract (M12).
 */
interface DeliveryChallanRepositoryInterface
{
    public function findById(int $id): DeliveryChallan;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): DeliveryChallan;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): DeliveryChallan;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
