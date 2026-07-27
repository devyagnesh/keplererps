<div class="card custom-card"><div class="card-body">
<form id="employeeForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">

<h6 class="fw-semibold mb-3">Personal &amp; Posting</h6>
<div class="row gy-3">
    <div class="col-md-5"><label class="form-label">Full Name *</label>
        <input type="text" class="form-control" name="full_name" value="{{ old('full_name', $employee?->full_name) }}" required>
    </div>
    <div class="col-md-3"><label class="form-label">Designation</label>
        <input type="text" class="form-control" name="designation" value="{{ old('designation', $employee?->designation) }}">
    </div>
    <div class="col-md-4"><label class="form-label">Department</label>
        <input type="text" class="form-control" name="department" value="{{ old('department', $employee?->department) }}">
    </div>
    <div class="col-md-4"><label class="form-label">Branch</label>
        <select class="form-select" name="branch_id">
            <option value="">None</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $employee?->branch_id) === (string) $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Default Shift</label>
        <select class="form-select" name="shift_id">
            <option value="">None</option>
            @foreach ($shifts as $shift)
                <option value="{{ $shift->id }}" @selected((string) old('shift_id', $employee?->shift_id) === (string) $shift->id)>{{ $shift->code }} — {{ $shift->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">ERP Login</label>
        <select class="form-select" name="user_id">
            <option value="">Not linked</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $employee?->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Mobile</label>
        <input type="text" class="form-control" name="mobile" value="{{ old('mobile', $employee?->mobile) }}">
    </div>
    <div class="col-md-4"><label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="{{ old('email', $employee?->email) }}">
    </div>
    <div class="col-md-2"><label class="form-label">Joined *</label>
        <input type="date" class="form-control" name="date_of_joining" value="{{ old('date_of_joining', $employee?->date_of_joining?->toDateString() ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-2"><label class="form-label">Exit Date</label>
        <input type="date" class="form-control" name="date_of_exit" value="{{ old('date_of_exit', $employee?->date_of_exit?->toDateString()) }}">
    </div>
    <div class="col-md-3"><label class="form-label">Status *</label>
        <select class="form-select" name="status" required>
            @foreach (\App\Enums\EmploymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $employee?->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

<h6 class="fw-semibold mt-4 mb-3">Salary</h6>
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Monthly Gross *</label>
        <input type="number" step="0.01" min="0" class="form-control" name="monthly_gross" value="{{ old('monthly_gross', $employee ? (float) $employee->monthly_gross : 0) }}" required>
    </div>
    <div class="col-md-3"><label class="form-label">Basic % of Gross *</label>
        <input type="number" step="0.01" min="1" max="100" class="form-control" name="basic_percent" value="{{ old('basic_percent', $employee ? (float) $employee->basic_percent : 50) }}" required>
    </div>
    <div class="col-md-3"><label class="form-label">Fixed Monthly Deduction</label>
        <input type="number" step="0.01" min="0" class="form-control" name="fixed_deduction" value="{{ old('fixed_deduction', $employee ? (float) $employee->fixed_deduction : 0) }}">
    </div>
    <div class="col-md-3"><label class="form-label">Overtime Rate / Hour</label>
        <input type="number" step="0.01" min="0" class="form-control" name="overtime_rate_per_hour" value="{{ old('overtime_rate_per_hour', $employee ? (float) $employee->overtime_rate_per_hour : 0) }}">
    </div>
    <div class="col-md-3"><label class="form-label">Bank Account No</label>
        <input type="text" class="form-control" name="bank_account_no" value="{{ old('bank_account_no', $employee?->bank_account_no) }}">
    </div>
    <div class="col-md-3"><label class="form-label">IFSC</label>
        <input type="text" class="form-control text-uppercase" name="ifsc_code" value="{{ old('ifsc_code', $employee?->ifsc_code) }}">
    </div>
    <div class="col-md-3"><label class="form-label">PAN</label>
        <input type="text" class="form-control text-uppercase" name="pan" value="{{ old('pan', $employee?->pan) }}">
    </div>
</div>

<h6 class="fw-semibold mt-4 mb-3">Statutory &amp; Biometric</h6>
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">UAN</label>
        <input type="text" class="form-control" name="uan" value="{{ old('uan', $employee?->uan) }}" maxlength="20">
    </div>
    <div class="col-md-3"><label class="form-label">PF Number</label>
        <input type="text" class="form-control" name="pf_number" value="{{ old('pf_number', $employee?->pf_number) }}" maxlength="30">
    </div>
    <div class="col-md-3"><label class="form-label">ESI Number</label>
        <input type="text" class="form-control" name="esi_number" value="{{ old('esi_number', $employee?->esi_number) }}" maxlength="30">
    </div>
    <div class="col-md-3"><label class="form-label">Aadhaar (last 4)</label>
        <input type="text" class="form-control" name="aadhaar_last4" value="{{ old('aadhaar_last4', $employee?->aadhaar_last4) }}" maxlength="4" inputmode="numeric">
    </div>
    <div class="col-md-3"><label class="form-label">Biometric Device Code</label>
        <input type="text" class="form-control" name="biometric_code" value="{{ old('biometric_code', $employee?->biometric_code) }}" maxlength="40">
        <div class="form-text">Used when importing punch CSV from the attendance device.</div>
    </div>
    <div class="col-md-12"><label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks" value="{{ old('remarks', $employee?->remarks) }}">
    </div>
</div>

<div class="mt-4">
    <button class="btn btn-primary" type="submit">Save Employee</button>
    <a href="{{ route('admin.employees.index') }}" class="btn btn-light">Cancel</a>
</div>
</form>
</div></div>
