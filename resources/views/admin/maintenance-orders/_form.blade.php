@php
    $editable = ! $order || $order->status->isEditable();
    $parts = old('parts', $order?->parts?->map(fn ($p) => [
        'item_id' => $p->item_id,
        'warehouse_id' => $p->warehouse_id,
        'quantity' => $p->quantity,
        'issued' => $p->issued,
    ])->all() ?? [['item_id' => '', 'warehouse_id' => '', 'quantity' => '']]);
    $prefillAsset = request('work_centre_id', $order?->work_centre_id);
    $prefillType = request('order_type', $order?->order_type?->value ?? 'breakdown');
@endphp
<div class="card custom-card mb-3"><div class="card-body">
<form id="masterForm" action="{{ $action }}" method="POST" novalidate data-editable="{{ $editable ? '1' : '0' }}">
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3">
    <div class="col-md-3">
        <label class="form-label">Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', $order?->document_date?->format('Y-m-d') ?? now()->toDateString()) }}" {{ $editable ? 'required' : 'disabled' }}>
    </div>
    <div class="col-md-3">
        <label class="form-label">Type *</label>
        <select name="order_type" class="form-select" {{ $order ? 'disabled' : 'required' }}>
            @foreach ($orderTypes as $type)
                <option value="{{ $type->value }}" @selected($prefillType === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @if ($order)<input type="hidden" name="order_type" value="{{ $order->order_type->value }}">@endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Asset *</label>
        <select name="work_centre_id" class="form-select" {{ $order ? 'disabled' : 'required' }}>
            <option value="">Select asset</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}" @selected((string) $prefillAsset === (string) $asset->id)>{{ $asset->code }} — {{ $asset->name }} ({{ $asset->status->label() }})</option>
            @endforeach
        </select>
        @if ($order)<input type="hidden" name="work_centre_id" value="{{ $order->work_centre_id }}">@endif
    </div>
    <div class="col-md-2">
        <label class="form-label">Assigned to</label>
        <select name="assigned_to" class="form-select" {{ $editable ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_to', $order?->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Reason</label>
        <input type="text" class="form-control" name="reason" value="{{ old('reason', $order?->reason) }}" {{ $editable ? '' : 'disabled' }}>
    </div>
    <div class="col-md-6">
        <label class="form-label">Action taken</label>
        <input type="text" class="form-control" name="action_taken" value="{{ old('action_taken', $order?->action_taken) }}" {{ $editable ? '' : 'disabled' }}>
    </div>
    <div class="col-12">
        <label class="form-label">Remarks</label>
        <textarea class="form-control" name="remarks" rows="2" {{ $editable ? '' : 'disabled' }}>{{ old('remarks', $order?->remarks) }}</textarea>
    </div>
    @if ($order)
    <div class="col-md-3"><label class="form-label text-muted">Downtime</label><div class="form-control-plaintext">{{ number_format((int) $order->downtime_minutes) }} min · ₹ {{ number_format((float) $order->downtime_cost, 2) }}</div></div>
    @endif
</div>

<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Spare parts</h6>
    @if ($editable)
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPart">Add part</button>
    @endif
</div>
<div id="partRows">
    @foreach ($parts as $index => $row)
        <div class="row g-2 mb-2 part-row">
            <div class="col-md-5">
                <select name="parts[{{ $index }}][item_id]" class="form-select" {{ $editable && empty($row['issued']) ? '' : 'disabled' }}>
                    <option value="">Item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((string) ($row['item_id'] ?? '') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="parts[{{ $index }}][warehouse_id]" class="form-select" {{ $editable && empty($row['issued']) ? '' : 'disabled' }}>
                    <option value="">Warehouse</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) ($row['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.0001" min="0" class="form-control" name="parts[{{ $index }}][quantity]" placeholder="Qty" value="{{ $row['quantity'] ?? '' }}" {{ $editable && empty($row['issued']) ? '' : 'disabled' }}>
            </div>
            <div class="col-md-2 d-flex align-items-center gap-2">
                @if (!empty($row['issued']))
                    <span class="badge bg-success">Issued</span>
                @elseif ($editable)
                    <button type="button" class="btn btn-sm btn-danger-light btn-remove-part">×</button>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if ($editable)
<div class="mt-4">
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('admin.maintenance-orders.index') }}" class="btn btn-light">Cancel</a>
</div>
@endif
</form>
</div></div>
