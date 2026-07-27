@can('ledger_account.update')
<a href="{{ route('admin.ledger-accounts.edit', $account) }}" class="btn btn-sm btn-primary-light">Edit</a>
@endcan
@if (! $account->is_system)
@can('ledger_account.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.ledger-accounts.destroy', $account) }}">Delete</button>
@endcan
@endif
