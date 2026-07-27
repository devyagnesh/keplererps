<?php

namespace App\Repositories\Interfaces;

use App\Models\Item;

/**
 * Data-access contract for items.
 */
interface ItemRepositoryInterface
{
    public function findById(int $id): Item;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Item;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): Item;

    public function delete(int $id): bool;

    public function nextItemCode(string $prefix = 'ITM'): string;

    public function findDuplicateName(string $name, int $categoryId, ?int $ignoreId = null): ?Item;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
