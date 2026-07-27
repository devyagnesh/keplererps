@php
    $isDraft = ! $productionEntry || ! $productionEntry->posted_at;
    $isCreate = ! $productionEntry;
    $rejectedQty = (float) old('rejected_quantity', $productionEntry?->rejected_quantity ?? 0);
    $showRejection = $rejectedQty > 0;
    $selectedWoId = old('work_order_id', $selectedWorkOrderId ?? $productionEntry?->work_order_id);
@endphp
<div class="card custom-card"><div class="card-body">
@if ($productionEntry && $productionEntry->posted_at)
<div class="alert alert-success mb-3">
    Posted on {{ $productionEntry->posted_at->format('d M Y H:i') }}
    @if ($productionEntry->operator)
        by {{ $productionEntry->operator->name }}
    @endif
</div>
@endif
<form id="productionEntryForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Work Order *</label>
        <select id="workOrderId" name="work_order_id" class="form-select select2" @disabled(! $isCreate) required>
            <option value="">Select work order</option>
            @foreach ($workOrders as $wo)
                <option value="{{ $wo->id }}"
                    data-item="{{ $wo->item?->item_code }} — {{ $wo->item?->item_name }}"
                    data-planned="{{ $wo->planned_quantity }}"
                    data-good="{{ $wo->good_quantity }}"
                    @selected((string) $selectedWoId === (string) $wo->id)
                >{{ $wo->document_no }} — {{ $wo->item?->item_code ?? '' }}</option>
            @endforeach
        </select>
        @unless ($isCreate)
            <input type="hidden" name="work_order_id" value="{{ $productionEntry->work_order_id }}">
        @endunless
        @if ($workOrder)
            <small class="text-muted d-block mt-1" id="workOrderSummary">
                {{ $workOrder->item?->item_code }} — {{ $workOrder->item?->item_name }}
                · Planned {{ number_format((float) $workOrder->planned_quantity, 4) }}
                · Good {{ number_format((float) $workOrder->good_quantity, 4) }}
            </small>
        @else
            <small class="text-muted d-block mt-1" id="workOrderSummary"></small>
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label">Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($productionEntry?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" @readonly(! $isDraft || ! $isCreate) required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Good Quantity</label>
        <input type="number" step="0.0001" min="0" class="form-control" id="goodQuantity" name="good_quantity" value="{{ old('good_quantity', $productionEntry?->good_quantity) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-2">
        <label class="form-label">Rejected Quantity</label>
        <input type="number" step="0.0001" min="0" class="form-control" id="rejectedQuantity" name="rejected_quantity" value="{{ old('rejected_quantity', $productionEntry?->rejected_quantity) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-4">
        <label class="form-label">Operator</label>
        <select name="operator_user_id" class="form-select select2" @disabled(! $isDraft || ! $isCreate)>
            <option value="">Current user</option>
            @foreach ($operators as $operator)
                <option value="{{ $operator->id }}" @selected((string) old('operator_user_id', $productionEntry?->operator_user_id ?? auth()->id()) === (string) $operator->id)>{{ $operator->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Batch No</label>
        <input type="text" class="form-control" name="batch_no" value="{{ old('batch_no', $productionEntry?->batch_no) }}" @readonly(! $isDraft || ! $isCreate) maxlength="50">
    </div>
    <div class="col-md-2">
        <label class="form-label">Start Time</label>
        <input type="time" class="form-control" name="start_time" value="{{ old('start_time', $productionEntry?->start_time ? substr((string) $productionEntry->start_time, 0, 5) : '') }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-2">
        <label class="form-label">End Time</label>
        <input type="time" class="form-control" name="end_time" value="{{ old('end_time', $productionEntry?->end_time ? substr((string) $productionEntry->end_time, 0, 5) : '') }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-2">
        <label class="form-label">Downtime (min)</label>
        <input type="number" step="1" min="0" class="form-control" name="downtime_minutes" value="{{ old('downtime_minutes', $productionEntry?->downtime_minutes ?? 0) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-4">
        <label class="form-label">Downtime Reason</label>
        <input type="text" class="form-control" name="downtime_reason" value="{{ old('downtime_reason', $productionEntry?->downtime_reason) }}" @readonly(! $isDraft || ! $isCreate) maxlength="100">
    </div>
    <div class="col-md-2">
        <label class="form-label">Machine Hours</label>
        <input type="number" step="0.0001" min="0" class="form-control" name="machine_hours" value="{{ old('machine_hours', $productionEntry?->machine_hours) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-2">
        <label class="form-label">Labour Hours</label>
        <input type="number" step="0.0001" min="0" class="form-control" name="labour_hours" value="{{ old('labour_hours', $productionEntry?->labour_hours) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
    <div class="col-md-8">
        <label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks" value="{{ old('remarks', $productionEntry?->remarks) }}" @readonly(! $isDraft || ! $isCreate)>
    </div>
</div>

<div id="rejectionFields" class="row gy-3 mb-3 @unless($showRejection) d-none @endunless">
    <div class="col-12"><h6 class="mb-0">Rejection Details</h6></div>
    <div class="col-md-4">
        <label class="form-label">Defect Reason</label>
        <select name="defect_reason_id" class="form-select select2" @disabled(! $isDraft || ! $isCreate)>
            <option value="">Select</option>
            @foreach ($defectReasons as $defectReason)
                <option value="{{ $defectReason->id }}" @selected((string) old('defect_reason_id', $productionEntry?->defect_reason_id) === (string) $defectReason->id)>{{ $defectReason->code }} — {{ $defectReason->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Disposition</label>
        <select id="rejectionDisposition" name="rejection_disposition" class="form-select" @disabled(! $isDraft || ! $isCreate)>
            <option value="">Select</option>
            @foreach ($dispositions as $disposition)
                <option value="{{ $disposition->value }}" @selected((string) old('rejection_disposition', $productionEntry?->rejection_disposition?->value) === (string) $disposition->value)>{{ $disposition->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 @unless(old('rejection_disposition', $productionEntry?->rejection_disposition?->value) === 'downgrade' && $showRejection) d-none @endunless" id="downgradeItemWrap">
        <label class="form-label">Downgrade Item</label>
        <select name="downgrade_item_id" class="form-select select2" @disabled(! $isDraft || ! $isCreate)>
            <option value="">Select item</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) old('downgrade_item_id', $productionEntry?->downgrade_item_id) === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
            @endforeach
        </select>
    </div>
</div>

@if ($isCreate)
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="post_immediately" id="postImmediately" value="1" @checked(old('post_immediately', '1'))>
    <label class="form-check-label" for="postImmediately">Post immediately to stock</label>
</div>
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Entry</button>
    <a href="{{ route('admin.production-entries.index') }}" class="btn btn-light">Cancel</a>
</div>
@endif
</form>
</div></div>

@if ($productionEntry && $productionEntry->posted_at && $productionEntry->materials->isNotEmpty())
<div class="card custom-card mt-3"><div class="card-header"><div class="card-title">Materials Consumed</div></div><div class="card-body">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Value</th>
                    <th>Issue Method</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($productionEntry->materials as $material)
                <tr>
                    <td>{{ $material->item?->item_code }} — {{ $material->item?->item_name }}</td>
                    <td>{{ number_format((float) $material->quantity, 4) }}</td>
                    <td>{{ number_format((float) $material->rate, 4) }}</td>
                    <td>{{ number_format((float) $material->value, 2) }}</td>
                    <td>{{ $material->issue_method?->label() ?? $material->issue_method }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div></div>
@endif

@if ($productionEntry && $productionEntry->posted_at)
<div class="card custom-card mt-3"><div class="card-header"><div class="card-title">Cost Summary</div></div><div class="card-body">
    <div class="row g-3">
        <div class="col-md-2">
            <label class="form-label text-muted mb-1">Material</label>
            <div class="fw-semibold">{{ number_format((float) $productionEntry->material_cost, 2) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted mb-1">Machine</label>
            <div class="fw-semibold">{{ number_format((float) $productionEntry->machine_cost, 2) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted mb-1">Labour</label>
            <div class="fw-semibold">{{ number_format((float) $productionEntry->labour_cost, 2) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted mb-1">Overhead</label>
            <div class="fw-semibold">{{ number_format((float) $productionEntry->overhead_cost, 2) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted mb-1">Total</label>
            <div class="fw-semibold">{{ number_format((float) $productionEntry->total_cost, 2) }}</div>
        </div>
    </div>
</div></div>
@endif
