<?php

namespace App\Repositories\Eloquent;

use App\Models\Party;
use App\Repositories\Interfaces\PartyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
/**
 * Eloquent implementation of PartyRepositoryInterface.
 */
class PartyRepository extends BaseRepository implements PartyRepositoryInterface
{
    public function __construct(Party $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $filters = []): Collection
    {
        return Party::query()
            ->with(['billingState:id,name,code', 'contacts'])
            ->when(! empty($filters['party_type']), fn (Builder $q) => $q->where('party_type', $filters['party_type']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->latest('id')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): Party
    {
        return Party::query()
            ->with(['billingState', 'addresses.state', 'contacts', 'assignedUser:id,name'])
            ->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Party
    {
        return Party::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): Party
    {
        $party = $this->findById($id);
        $party->update($data);

        return $party->fresh(['billingState', 'addresses', 'contacts']);
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
    public function findByName(string $name): ?Party
    {
        return Party::query()
            ->whereRaw('LOWER(party_name) = ?', [mb_strtolower($name)])
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function nextPartyCode(): string
    {
        $latest = Party::query()
            ->withTrashed()
            ->select(['id', 'party_code'])
            ->orderByDesc('id')
            ->value('party_code');

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PTY-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
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
        $orderColumnIndex = (int) data_get($params, 'order.0.column', 0);
        $orderDir = data_get($params, 'order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $columns = ['id', 'party_code', 'party_name', 'party_type', 'gstin', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $base = Party::query()->with('billingState:id,name');
        $recordsTotal = (clone $base)->count();

        $filtered = (clone $base)
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('party_code', 'like', "%{$search}%")
                        ->orWhere('party_name', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%");
                });
            })
            ->when(! empty($params['party_type']), function (Builder $q) use ($params): void {
                $type = $params['party_type'];
                $q->where(function (Builder $inner) use ($type): void {
                    $inner->where('party_type', $type)
                        ->orWhere('party_type', 'both');
                });
            })
            ->when(! empty($params['status']), fn (Builder $q) => $q->where('status', $params['status']));

        $recordsFiltered = (clone $filtered)->count();

        $rows = $filtered
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        $data = $rows->map(function (Party $party): array {
            return [
                'id' => $party->id,
                'party_code' => $party->party_code,
                'party_name' => e($party->party_name),
                'party_type' => ucfirst($party->party_type->value),
                'gstin' => $party->gstin ?? '—',
                'state' => $party->billingState?->name ?? '—',
                'status' => match ($party->status->value) {
                    'active' => '<span class="badge bg-success-transparent">Active</span>',
                    'inactive' => '<span class="badge bg-secondary-transparent">Inactive</span>',
                    default => '<span class="badge bg-danger-transparent">Blocked</span>',
                },
                'action' => view('admin.parties.partials.actions', ['party' => $party])->render(),
            ];
        })->all();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}
