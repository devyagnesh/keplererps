<a href="{{ route('admin.purchase-returns.edit', $return) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($return->status->value === 'draft')
@can('purchase_return.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.purchase-returns.destroy', $return) }}">Delete</button>
@endcan
@endif
