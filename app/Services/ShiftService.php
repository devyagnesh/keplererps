<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Shift master business logic (M14).
 */
class ShiftService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Shift
    {
        $data['code'] = strtoupper(trim((string) $data['code']));

        return Shift::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Shift
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        $shift = Shift::query()->findOrFail($id);
        $shift->update($data);

        return $shift->refresh();
    }

    public function delete(int $id): bool
    {
        $shift = Shift::query()->findOrFail($id);

        if ($shift->employees()->exists()) {
            throw ValidationException::withMessages([
                'shift' => 'Employees are assigned to this shift. Reassign them or mark the shift inactive.',
            ]);
        }

        return (bool) $shift->delete();
    }

    /**
     * Active shifts for pickers.
     *
     * @return Collection<int, Shift>
     */
    public function selectable(): Collection
    {
        return Shift::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'start_time', 'end_time', 'break_minutes']);
    }

    /**
     * All shifts for the master listing.
     *
     * @return Collection<int, Shift>
     */
    public function all(): Collection
    {
        return Shift::query()->orderBy('code')->get();
    }
}
