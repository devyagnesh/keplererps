<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.hsn-codes.edit', $hsnCode) }}" class="btn btn-primary-light"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-master" data-url="{{ route('admin.hsn-codes.destroy', $hsnCode) }}"><i class="ri-delete-bin-line"></i></button>
</div>
