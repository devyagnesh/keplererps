<?php

namespace App\Services;

use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Repositories\Interfaces\LedgerAccountRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Chart-of-accounts business logic (M13).
 */
class LedgerAccountService
{
    public function __construct(protected LedgerAccountRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): LedgerAccount
    {
        return $this->repository->findById($id);
    }

    /**
     * Resolve a ledger by its code, or null when the chart has not been set up.
     */
    public function findByCode(string $code): ?LedgerAccount
    {
        return $this->repository->findByCode($code);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LedgerAccount
    {
        $this->assertParent($data);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['is_system'] = false;

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): LedgerAccount
    {
        $account = $this->repository->findById($id);
        unset($data['is_system']);

        if ($account->is_system) {
            // Control accounts keep their code and type so posting rules stay valid.
            unset($data['code'], $data['account_type']);
        }

        $this->assertParent($data, $id);
        $data['updated_by'] = Auth::id();

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $account = $this->repository->findById($id);

        if ($account->is_system) {
            throw ValidationException::withMessages([
                'ledger_account' => 'System control accounts cannot be deleted.',
            ]);
        }

        if ($account->journalLines()->exists()) {
            throw ValidationException::withMessages([
                'ledger_account' => 'This account is already used by journal vouchers.',
            ]);
        }

        if ($account->children()->exists()) {
            throw ValidationException::withMessages([
                'ledger_account' => 'Reassign the child accounts before deleting this account.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Selectable postable accounts for voucher line pickers.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function selectable(): Collection
    {
        return LedgerAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertParent(array $data, ?int $id = null): void
    {
        if (empty($data['parent_id'])) {
            return;
        }

        $parentId = (int) $data['parent_id'];
        if ($id !== null && $parentId === $id) {
            throw ValidationException::withMessages([
                'parent_id' => 'An account cannot be its own parent.',
            ]);
        }

        $parent = LedgerAccount::query()->findOrFail($parentId);
        $type = $data['account_type'] ?? null;
        $accountType = $type instanceof LedgerAccountType ? $type : LedgerAccountType::tryFrom((string) $type);

        if ($accountType !== null && $parent->account_type !== $accountType) {
            throw ValidationException::withMessages([
                'parent_id' => 'Parent account must have the same account type.',
            ]);
        }
    }
}
