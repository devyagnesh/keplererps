<?php

namespace App\Repositories\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared server-side DataTables builder for simple master listings.
 */
trait BuildsServerSideDataTable
{
    /**
     * Build a DataTables JSON payload.
     *
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @param  list<string>  $searchable
     * @param  callable(Model): array<string, mixed>  $mapper
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    protected function buildDataTable(Builder $query, array $columns, array $searchable, callable $mapper, array $params): array
    {
        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 25);
        $search = trim((string) data_get($params, 'search.value', ''));
        $orderColumnIndex = (int) data_get($params, 'order.0.column', 0);
        $orderDir = data_get($params, 'order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn = $columns[$orderColumnIndex] ?? $columns[0];

        $recordsTotal = (clone $query)->count();

        $filtered = (clone $query)->when($search !== '', function (Builder $q) use ($search, $searchable): void {
            $q->where(function (Builder $inner) use ($search, $searchable): void {
                foreach ($searchable as $index => $column) {
                    if ($index === 0) {
                        $inner->where($column, 'like', "%{$search}%");
                    } else {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        });

        if (array_key_exists('is_active', $params) && $params['is_active'] !== '' && $params['is_active'] !== null) {
            $filtered->where('is_active', (bool) $params['is_active']);
        }

        $recordsFiltered = (clone $filtered)->count();

        $rows = $filtered
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map($mapper)->values()->all(),
        ];
    }
}
