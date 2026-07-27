<?php

namespace App\Repositories\Interfaces;

use App\Models\Party;
use Illuminate\Support\Collection;

/**
 * Data-access contract for parties (customers/suppliers).
 */
interface PartyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Party>
     */
    public function all(array $filters = []): Collection;

    public function findById(int $id): Party;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Party;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Party;

    public function delete(int $id): bool;

    public function findByName(string $name): ?Party;

    public function nextPartyCode(): string;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
