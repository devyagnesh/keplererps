<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-warehouse" data-url="{{ route('admin.warehouses.destroy', $warehouse) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
</div>
