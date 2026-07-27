<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.stock-transfers.edit', $stockTransfer) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    @if ($stockTransfer->status->value === 'draft')
        <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.stock-transfers.destroy', $stockTransfer) }}"><i class="ri-delete-bin-line"></i></button>
    @endif
</div>
