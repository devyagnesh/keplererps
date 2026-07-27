<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-item" data-url="{{ route('admin.items.destroy', $item) }}"><i class="ri-delete-bin-line"></i></button>
</div>
