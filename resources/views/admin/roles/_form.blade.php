@php $selected = old('permission_ids', $role?->permissions?->pluck('id')->all() ?? []); @endphp
<div class="card custom-card"><div class="card-body">
<form id="roleForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-4">
    <div class="col-md-4"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $role?->name) }}" {{ $role?->is_system ? 'readonly' : '' }} required></div>
    <div class="col-md-4"><label class="form-label">Slug *</label><input type="text" class="form-control" name="slug" value="{{ old('slug', $role?->slug) }}" {{ $role?->is_system ? 'readonly' : '' }} required></div>
    <div class="col-md-4"><label class="form-label">Level</label><input type="number" class="form-control" name="level" value="{{ old('level', $role?->level ?? 0) }}"></div>
    <div class="col-md-12"><label class="form-label">Description</label><input type="text" class="form-control" name="description" value="{{ old('description', $role?->description) }}"></div>
    <div class="col-md-3"><div class="form-check form-switch"><input type="hidden" name="require_2fa" value="0"><input class="form-check-input" type="checkbox" name="require_2fa" value="1" {{ old('require_2fa', $role?->require_2fa) ? 'checked' : '' }}><label class="form-check-label">Require 2FA</label></div></div>
    <div class="col-md-3"><div class="form-check form-switch"><input type="hidden" name="simplified_ui" value="0"><input class="form-check-input" type="checkbox" name="simplified_ui" value="1" {{ old('simplified_ui', $role?->simplified_ui) ? 'checked' : '' }}><label class="form-check-label">Simplified UI</label></div></div>
    <div class="col-md-3"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $role?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
</div>
<h6 class="fw-semibold mb-3">Permission Matrix</h6>
@foreach($permissionGroups as $group => $permissions)
    <div class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>{{ ucfirst(str_replace('_', ' ', $group)) }}</strong>
            <label class="form-check mb-0"><input type="checkbox" class="form-check-input select-module" data-module="{{ $group }}"> Select all</label>
        </div>
        <div class="row">
            @foreach($permissions as $permission)
                <div class="col-md-3 mb-2">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input perm-{{ $group }}" name="permission_ids[]" value="{{ $permission->id }}" {{ in_array($permission->id, $selected, true) ? 'checked' : '' }}>
                        {{ $permission->label }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
<button type="submit" class="btn btn-primary">Save Role</button>
<a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
</form>
</div></div>
