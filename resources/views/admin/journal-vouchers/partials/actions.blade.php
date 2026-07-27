<a href="{{ route('admin.journal-vouchers.edit', $voucher) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($voucher->status->value === 'draft' && $voucher->source_type === null)
@can('journal_voucher.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.journal-vouchers.destroy', $voucher) }}">Delete</button>
@endcan
@endif
