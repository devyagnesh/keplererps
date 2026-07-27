<a href="{{ route('admin.sales-orders.edit', $salesOrder) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($salesOrder->status->value === 'draft')
@can('sales_order.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.sales-orders.destroy', $salesOrder) }}">Delete</button>
@endcan
@endif
