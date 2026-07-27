<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent category repository.
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): Category
    {
        return Category::query()->with('parent')->findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    public function update(int $id, array $data): Category
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh(['parent']);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    public function hasChildren(int $id): bool
    {
        return Category::query()->where('parent_id', $id)->exists();
    }

    public function options(): Collection
    {
        return Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function getForDataTable(array $params): array
    {
        return $this->buildDataTable(
            Category::query()->with('parent:id,name'),
            ['id', 'code', 'name', 'category_type', 'is_active', 'created_at'],
            ['code', 'name'],
            function (Category $category): array {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => e($category->name),
                    'parent' => $category->parent?->name ?? '—',
                    'category_type' => ucfirst($category->category_type),
                    'is_active' => $category->is_active
                        ? '<span class="badge bg-success-transparent">Active</span>'
                        : '<span class="badge bg-danger-transparent">Inactive</span>',
                    'action' => view('admin.categories.partials.actions', ['category' => $category])->render(),
                ];
            },
            $params
        );
    }
}
