<?php

namespace App\Repositories\Interfaces;

use App\Models\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function findById(int $id): SalesOrder;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): SalesOrder;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): SalesOrder;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
