<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.uoms.edit', $uom) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.uoms.destroy', $uom) }}"><i class="ri-delete-bin-line"></i></button>
</div>
