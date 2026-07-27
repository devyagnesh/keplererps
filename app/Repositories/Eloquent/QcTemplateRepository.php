<?php

namespace App\Repositories\Eloquent;

use App\Models\QcTemplate;
use App\Repositories\Interfaces\QcTemplateRepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Eloquent QC template repository.
 */
class QcTemplateRepository implements QcTemplateRepositoryInterface
{
    public function __construct(protected QcTemplate $model) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): QcTemplate
    {
        return $this->model->newQuery()
            ->with(['item:id,item_code,item_name', 'category:id,name', 'parameters'])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): QcTemplate
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): QcTemplate
    {
        $template = $this->findById($id);
        $data['updated_by'] = Auth::id();
        $template->update($data);

        return $template->fresh(['item:id,item_code,item_name', 'category:id,name', 'parameters']);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getForDataTable(array $params): array
    {
        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 25);
        $search = trim((string) data_get($params, 'search.value', ''));
        $orderCol = (int) data_get($params, 'order.0.column', 0);
        $orderDir = strtolower((string) data_get($params, 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = ['id', 'code', 'name', 'inspection_type', 'sampling_plan', 'is_active', 'id'];
        $orderBy = $columns[$orderCol] ?? 'id';

        $base = $this->model->newQuery()->with(['item:id,item_code,item_name', 'category:id,name']);
        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search): void {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $params) && $params['is_active'] !== '' && $params['is_active'] !== null) {
            $base->where('is_active', filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $recordsFiltered = (clone $base)->count();
        $rows = $base->orderBy($orderBy, $orderDir)->skip($start)->take($length)->get();

        $data = $rows->map(function (QcTemplate $row): array {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'name' => e($row->name),
                'inspection_type' => e($row->inspection_type->label()),
                'sampling_plan' => e($row->sampling_plan->label()),
                'scope' => e($row->item?->item_code ?? $row->category?->name ?? 'All'),
                'is_active' => $row->is_active ? 'Active' : 'Inactive',
                'action' => view('admin.qc-templates.partials.actions', ['template' => $row])->render(),
            ];
        })->all();

        return compact('draw', 'recordsTotal', 'recordsFiltered', 'data');
    }
}
