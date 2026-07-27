@php
    $selectedRoles = old('role_ids', $userModel?->roles?->pluck('id')->all() ?? []);
    $scopeType = old('scope_type', $userModel?->dataScope?->scope_type?->value ?? 'all');
@endphp
<div class="card custom-card"><div class="card-body">
<form id="userForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-4"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="name" value="{{ old('name', $userModel?->name) }}" required></div>
    <div class="col-md-4"><label class="form-label">Username *</label><input type="text" class="form-control" name="username" value="{{ old('username', $userModel?->username) }}" {{ $userModel ? 'readonly' : '' }} required></div>
    <div class="col-md-4"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" value="{{ old('email', $userModel?->email) }}" required></div>
    <div class="col-md-4"><label class="form-label">Mobile *</label><input type="text" class="form-control" name="mobile" value="{{ old('mobile', $userModel?->mobile) }}" required></div>
    <div class="col-md-4"><label class="form-label">Password {{ $userModel ? '' : '*' }}</label><input type="password" class="form-control" name="password" autocomplete="new-password"></div>
    <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="password_confirmation" autocomplete="new-password"></div>
    <div class="col-md-4">
        <label class="form-label">Home Branch *</label>
        <select class="form-control select2" name="branch_id" required>
            <option value="">Select branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $userModel?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Data Scope *</label>
        <select class="form-control" name="scope_type" id="scope_type">
            @foreach($scopeTypes as $type)
                <option value="{{ $type->value }}" {{ $scopeType === $type->value ? 'selected' : '' }}>{{ ucfirst($type->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Roles *</label>
        <select class="form-control select2" name="role_ids[]" multiple required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ in_array($role->id, $selectedRoles, true) ? 'selected' : '' }}>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Valid From</label><input type="date" class="form-control" name="valid_from" value="{{ old('valid_from', optional($userModel?->valid_from)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Valid Until</label><input type="date" class="form-control" name="valid_until" value="{{ old('valid_until', optional($userModel?->valid_until)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="require_2fa" value="0"><input class="form-check-input" type="checkbox" name="require_2fa" value="1" {{ old('require_2fa', $userModel?->require_2fa) ? 'checked' : '' }}><label class="form-check-label">Require 2FA</label></div></div>
    <div class="col-md-3"><div class="form-check form-switch mt-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $userModel?->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button type="submit" class="btn btn-primary">Save User</button><a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a></div>
</div>
</form>
</div></div>
