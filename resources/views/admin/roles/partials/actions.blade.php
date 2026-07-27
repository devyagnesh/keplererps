<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    <button type="button" class="btn btn-secondary-light btn-copy-role" data-url="{{ route('admin.roles.copy', $role) }}" title="Copy"><i class="ri-file-copy-line"></i></button>
    @unless($role->is_system)
        <button type="button" class="btn btn-danger-light btn-delete-role" data-url="{{ route('admin.roles.destroy', $role) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
    @endunless
</div>
