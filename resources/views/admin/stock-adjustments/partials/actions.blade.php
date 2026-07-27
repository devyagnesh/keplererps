<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.stock-adjustments.edit', $stockAdjustment) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    @if ($stockAdjustment->status->value === 'draft')
        <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.stock-adjustments.destroy', $stockAdjustment) }}"><i class="ri-delete-bin-line"></i></button>
    @endif
</div>
