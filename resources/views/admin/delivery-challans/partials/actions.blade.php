<a href="{{ route('admin.delivery-challans.edit', $challan) }}" class="btn btn-sm btn-primary-light">Open</a>
<a href="{{ route('admin.delivery-challans.print', $challan) }}" class="btn btn-sm btn-light" target="_blank">Print</a>
@if ($challan->status->value === 'draft')
@can('delivery_challan.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.delivery-challans.destroy', $challan) }}">Delete</button>
@endcan
@endif
