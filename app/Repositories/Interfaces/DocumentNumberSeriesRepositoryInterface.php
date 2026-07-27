<?php

namespace App\Repositories\Interfaces;

use App\Models\DocumentNumberSeries;

/**
 * Data-access contract for document number series.
 */
interface DocumentNumberSeriesRepositoryInterface
{
    public function findById(int $id): DocumentNumberSeries;

    /** @param  array<string, mixed>  $data */
    public function create(array $data): DocumentNumberSeries;

    /** @param  array<string, mixed>  $data */
    public function update(int $id, array $data): DocumentNumberSeries;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function getForDataTable(array $params): array;
}
