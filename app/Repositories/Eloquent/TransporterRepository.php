<?php

namespace App\Repositories\Eloquent;

use App\Models\Transporter;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\TransporterRepositoryInterface;

/**
 * Eloquent transporter repository.
 */
class TransporterRepository implements TransporterRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Transporter
    {
        return Transporter::query()->findOrFail($id);
    }

    public function create(array $data): Transporter
    {
        return Transporter::query()->create($data);
    }

    public function update(int $id, array $data): Transporter
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            Transporter::query(),
            ['id', 'code', 'name', 'gstin', 'is_active', 'created_at'],
            ['code', 'name', 'gstin', 'phone'],
            function (Transporter $transporter): array {
                return [
                    'id' => $transporter->id,
                    'code' => $transporter->code,
                    'name' => e($transporter->name),
                    'gstin' => $transporter->gstin ?? '—',
                    'phone' => $transporter->phone ?? '—',
                    'is_active' => $transporter->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.transporters.partials.actions', ['transporter' => $transporter])->render(),
                ];
            },
            $params
        );
    }
}
