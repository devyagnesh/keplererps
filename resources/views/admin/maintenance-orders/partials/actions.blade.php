<a href="{{ route('admin.maintenance-orders.edit', $order) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($order->status->isEditable())
@can('maintenance_order.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.maintenance-orders.destroy', $order) }}">Cancel</button>
@endcan
@endif
