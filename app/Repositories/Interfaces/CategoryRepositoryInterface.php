<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * Data-access contract for categories.
 */
interface CategoryRepositoryInterface
{
    public function findById(int $id): Category;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Category;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): Category;

    public function delete(int $id): bool;

    public function hasChildren(int $id): bool;

    /** @return Collection<int, Category> */
    public function options(): Collection;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
