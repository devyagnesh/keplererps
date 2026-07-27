<a href="{{ route('admin.sales-quotations.edit', $quotation) }}" class="btn btn-sm btn-primary-light">Open</a>
<a href="{{ route('admin.sales-quotations.print', $quotation) }}" class="btn btn-sm btn-light" target="_blank">Print</a>
@if ($quotation->status->value === 'draft')
@can('sales_quotation.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.sales-quotations.destroy', $quotation) }}">Delete</button>
@endcan
@endif
