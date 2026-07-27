<a href="{{ route('admin.sales-returns.edit', $return) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($return->status->value === 'draft')
@can('sales_return.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.sales-returns.destroy', $return) }}">Delete</button>
@endcan
@endif
