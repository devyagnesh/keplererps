<a href="{{ route('admin.work-orders.edit', $workOrder) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($workOrder->status->value === 'draft')
@can('work_order.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.work-orders.destroy', $workOrder) }}">Delete</button>
@endcan
@endif
