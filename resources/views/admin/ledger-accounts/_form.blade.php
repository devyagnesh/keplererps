<div class="card custom-card"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3"><label class="form-label">Code *</label>
        <input type="text" class="form-control" name="code" value="{{ old('code', $account?->code) }}" {{ $account?->is_system ? 'readonly' : '' }} required>
    </div>
    <div class="col-md-5"><label class="form-label">Name *</label>
        <input type="text" class="form-control" name="name" value="{{ old('name', $account?->name) }}" required>
    </div>
    <div class="col-md-4"><label class="form-label">Account Type *</label>
        <select class="form-select" name="account_type" {{ $account?->is_system ? 'disabled' : '' }} required>
            @foreach (\App\Enums\LedgerAccountType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('account_type', $account?->account_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @if ($account?->is_system)<input type="hidden" name="account_type" value="{{ $account->account_type->value }}">@endif
    </div>
    <div class="col-md-4"><label class="form-label">Reporting Group</label>
        <input type="text" class="form-control" name="account_group" value="{{ old('account_group', $account?->account_group) }}">
    </div>
    <div class="col-md-4"><label class="form-label">Parent Account</label>
        <select class="form-select select2" name="parent_id">
            <option value="">None</option>
            @foreach ($accounts as $option)
                @continue($account && $option->id === $account->id)
                <option value="{{ $option->id }}" @selected((string) old('parent_id', $account?->parent_id) === (string) $option->id)>
                    {{ $option->code }} — {{ $option->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">Opening Balance</label>
        <input type="number" step="0.01" class="form-control" name="opening_balance" value="{{ old('opening_balance', $account?->opening_balance ?? 0) }}">
    </div>
    <div class="col-md-2"><label class="form-label">Balance Side</label>
        <select class="form-select" name="opening_balance_side">
            @foreach (\App\Enums\BalanceSide::cases() as $side)
                <option value="{{ $side->value }}" @selected(old('opening_balance_side', $account?->opening_balance_side?->value ?? 'debit') === $side->value)>{{ $side->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8"><label class="form-label">Description</label>
        <input type="text" class="form-control" name="description" value="{{ old('description', $account?->description) }}">
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $account?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('admin.ledger-accounts.index') }}" class="btn btn-light">Cancel</a>
    </div>
</div>
</form>
</div></div>
