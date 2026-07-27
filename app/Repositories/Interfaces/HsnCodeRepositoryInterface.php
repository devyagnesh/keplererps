<?php

namespace App\Repositories\Interfaces;

use App\Models\HsnCode;
use Illuminate\Support\Collection;

/**
 * Data-access contract for HSN/SAC codes.
 */
interface HsnCodeRepositoryInterface
{
    public function findById(int $id): HsnCode;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): HsnCode;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): HsnCode;

    public function delete(int $id): bool;

    /** @return Collection<int, HsnCode> */
    public function activeOptions(): Collection;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
