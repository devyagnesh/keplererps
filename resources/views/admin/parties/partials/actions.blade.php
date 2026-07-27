<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.parties.edit', $party) }}" class="btn btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-party" data-url="{{ route('admin.parties.destroy', $party) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
</div>
