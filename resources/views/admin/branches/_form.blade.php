<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <form id="branchForm" action="{{ $action }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="_method" value="{{ $method }}">
                    <div class="row gy-3">
                        <div class="col-xl-4">
                            <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="code" name="code" value="{{ old('code', $branch?->code) }}">
                        </div>
                        <div class="col-xl-8">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $branch?->name) }}">
                        </div>
                        <div class="col-xl-12">
                            <label class="form-label" for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $branch?->address) }}</textarea>
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="state_id">State</label>
                            <select class="form-control select2" id="state_id" name="state_id">
                                <option value="">Select state</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ (string) old('state_id', $branch?->state_id) === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->code }} — {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="pin_code">PIN Code</label>
                            <input type="text" class="form-control" id="pin_code" name="pin_code" maxlength="6" value="{{ old('pin_code', $branch?->pin_code) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $branch?->phone) }}">
                        </div>
                        <div class="col-xl-4">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $branch?->email) }}">
                        </div>
                        <div class="col-xl-4">
                            <div class="form-check form-switch mt-4">
                                <input type="hidden" name="is_head_office" value="0">
                                <input class="form-check-input" type="checkbox" id="is_head_office" name="is_head_office" value="1" {{ old('is_head_office', $branch?->is_head_office) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_head_office">Head Office</label>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="form-check form-switch mt-4">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $branch?->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <button type="submit" class="btn btn-primary">Save Branch</button>
                            <a href="{{ route('admin.branches.index') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
