<div class="hstack gap-2 fs-15">
    @can('packing_unit.update')
    <a href="{{ route('admin.packing-units.edit', $unit) }}" class="btn btn-icon btn-sm btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    @endcan
    @can('packing_unit.delete')
    <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.packing-units.destroy', $unit) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
    @endcan
</div>
