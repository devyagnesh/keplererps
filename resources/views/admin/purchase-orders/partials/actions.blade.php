<a href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-primary-light">Open</a>
<a href="{{ route('admin.purchase-orders.print', $purchaseOrder) }}" class="btn btn-sm btn-light" target="_blank">Print</a>
@if ($purchaseOrder->status->value === 'draft')
@can('purchase_order.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.purchase-orders.destroy', $purchaseOrder) }}">Delete</button>
@endcan
@endif
