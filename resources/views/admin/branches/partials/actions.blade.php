<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-branch" data-url="{{ route('admin.branches.destroy', $branch) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
</div>
