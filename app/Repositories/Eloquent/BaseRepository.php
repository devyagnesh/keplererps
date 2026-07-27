<?php

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared CRUD helpers for Eloquent repositories.
 */
abstract class BaseRepository
{
    /**
     * @param  Model  $model  Concrete Eloquent model instance.
     */
    public function __construct(protected Model $model) {}

    /**
     * Find a record by primary key or fail.
     */
    public function findById(int $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * Persist a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * Soft delete a record.
     */
    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }

    /**
     * Return all records optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(array $filters = []): Collection
    {
        return $this->model->newQuery()->latest('id')->get();
    }
}
