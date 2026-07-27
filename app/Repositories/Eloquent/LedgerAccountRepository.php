<?php

namespace App\Repositories\Eloquent;

use App\Models\LedgerAccount;
use App\Repositories\Eloquent\Concerns\BuildsServerSideDataTable;
use App\Repositories\Interfaces\LedgerAccountRepositoryInterface;

/**
 * Eloquent chart-of-accounts repository.
 */
class LedgerAccountRepository implements LedgerAccountRepositoryInterface
{
    use BuildsServerSideDataTable;

    public function findById(int $id): LedgerAccount
    {
        return LedgerAccount::query()
            ->with(['parent:id,code,name', 'party:id,party_code,party_name'])
            ->findOrFail($id);
    }

    public function findByCode(string $code): ?LedgerAccount
    {
        return LedgerAccount::query()->where('code', $code)->first();
    }

    public function create(array $data): LedgerAccount
    {
        return LedgerAccount::query()->create($data);
    }

    public function update(int $id, array $data): LedgerAccount
    {
        $record = LedgerAccount::query()->findOrFail($id);
        $record->update($data);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return (bool) LedgerAccount::query()->findOrFail($id)->delete();
    }

    public function getForDataTable(array $params): array
    {
        $query = LedgerAccount::query()->with(['parent:id,code,name']);

        if (! empty($params['account_type'])) {
            $query->where('account_type', $params['account_type']);
        }

        return $this->buildDataTable(
            $query,
            ['id', 'code', 'name', 'account_type', 'account_group', 'is_active', 'created_at'],
            ['code', 'name', 'account_group'],
            function (LedgerAccount $account): array {
                return [
                    'id' => $account->id,
                    'code' => e($account->code),
                    'name' => e($account->name),
                    'account_type' => $account->account_type->label(),
                    'account_group' => e($account->account_group ?? '—'),
                    'parent' => $account->parent ? e($account->parent->name) : '—',
                    'opening_balance' => number_format((float) $account->opening_balance, 2, '.', '')
                        .' '.strtoupper(substr($account->opening_balance_side->value, 0, 2)),
                    'is_active' => $account->is_active ? 'Active' : 'Inactive',
                    'action' => view('admin.ledger-accounts.partials.actions', ['account' => $account])->render(),
                ];
            },
            $params
        );
    }
}
