<a href="{{ route('admin.purchase-bills.edit', $bill) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($bill->status->isEditable())
@can('purchase_bill.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.purchase-bills.destroy', $bill) }}">Delete</button>
@endcan
@endif
