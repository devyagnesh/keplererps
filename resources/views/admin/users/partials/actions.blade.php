<div class="btn-group btn-group-sm">
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    <a href="{{ route('admin.users.permissions', $user) }}" class="btn btn-info-light" title="Effective permissions"><i class="ri-shield-keyhole-line"></i></a>
    <button type="button" class="btn btn-danger-light btn-delete-user" data-url="{{ route('admin.users.destroy', $user) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
</div>
