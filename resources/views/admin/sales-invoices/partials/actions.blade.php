<a href="{{ route('admin.sales-invoices.edit', $invoice) }}" class="btn btn-sm btn-primary-light">Open</a>
<a href="{{ route('admin.sales-invoices.print', $invoice) }}" class="btn btn-sm btn-light" target="_blank">Print</a>
@if ($invoice->status->value === 'draft')
@can('sales_invoice.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.sales-invoices.destroy', $invoice) }}">Delete</button>
@endcan
@endif
