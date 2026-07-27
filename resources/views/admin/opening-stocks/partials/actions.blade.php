<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.opening-stocks.edit', $openingStock) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    @if ($openingStock->status->value === 'draft')
        <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.opening-stocks.destroy', $openingStock) }}"><i class="ri-delete-bin-line"></i></button>
    @endif
</div>
