<?php

namespace App\Repositories\Interfaces;

use App\Models\PackageLabel;
use Illuminate\Support\Collection;

/**
 * Data-access contract for scannable package labels (M17).
 */
interface PackageLabelRepositoryInterface
{
    public function findById(int $id): PackageLabel;

    public function findByLabelNo(string $labelNo): ?PackageLabel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PackageLabel;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;

    /**
     * Active packages belonging to a challan.
     *
     * @return Collection<int, PackageLabel>
     */
    public function forChallan(int $deliveryChallanId): Collection;
}
