<?php

namespace App\Repositories\Eloquent;

use App\Models\HsnCode;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\HsnCodeRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent HSN/SAC repository.
 */
class HsnCodeRepository implements HsnCodeRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): HsnCode
    {
        return HsnCode::query()->findOrFail($id);
    }

    public function create(array $data): HsnCode
    {
        return HsnCode::query()->create($data);
    }

    public function update(int $id, array $data): HsnCode
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function activeOptions(): Collection
    {
        return HsnCode::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'description', 'default_gst_rate', 'code_type']);
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            HsnCode::query(),
            ['id', 'code', 'code_type', 'description', 'default_gst_rate', 'is_active'],
            ['code', 'description'],
            function (HsnCode $hsn): array {
                return [
                    'id' => $hsn->id,
                    'code' => $hsn->code,
                    'code_type' => strtoupper($hsn->code_type),
                    'description' => e($hsn->description),
                    'default_gst_rate' => $hsn->default_gst_rate,
                    'is_active' => $hsn->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.hsn-codes.partials.actions', ['hsnCode' => $hsn])->render(),
                ];
            },
            $params
        );
    }
}
