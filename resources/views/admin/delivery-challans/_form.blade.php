@php
    $isDraft = ! $deliveryChallan || $deliveryChallan->status->value === 'draft';
    $lines = old('items', $deliveryChallan?->items?->map(fn ($l) => [
        'sales_order_item_id' => $l->sales_order_item_id,
        'quantity' => $l->quantity,
        'batch_id' => $l->batch_id,
        'description' => $l->description,
        'item_label' => ($l->item?->item_code ?? '').' — '.($l->item?->item_name ?? ''),
        'tracking_type' => $l->item?->tracking_type?->value ?? 'none',
        'batch_label' => $l->batch?->batch_no ?? '',
    ])->toArray() ?? []);
    if ($lines === [] && ! empty($pendingLines)) {
        $lines = collect($pendingLines)->map(fn ($p) => [
            'sales_order_item_id' => $p['sales_order_item_id'],
            'quantity' => $p['pending_qty'],
            'batch_id' => '',
            'description' => $p['description'] ?? '',
            'item_label' => ($p['item_code'] ?? '').' — '.($p['item_name'] ?? ''),
            'tracking_type' => $p['tracking_type'] ?? 'none',
            'batch_label' => '',
        ])->all();
    }
@endphp
<div class="card custom-card"><div class="card-body">
<form id="deliveryChallanForm" action="{{ $action }}" method="POST" novalidate data-pending-lines-url="{{ url('/admin/delivery-challans/pending-lines') }}">
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($deliveryChallan?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isDraft ? '' : 'readonly' }} required>
    </div>
    <div class="col-md-5">
        <label class="form-label">Sales Order *</label>
        <select name="sales_order_id" id="salesOrderId" class="form-select select2" {{ $deliveryChallan ? 'disabled' : '' }} required>
            <option value="">Select sales order</option>
            @foreach ($salesOrders as $order)
                <option value="{{ $order->id }}" @selected((string) old('sales_order_id', $selectedSalesOrderId ?? $deliveryChallan?->sales_order_id) === (string) $order->id)>
                    {{ $order->document_no }} — {{ $order->customer?->party_name }}
                </option>
            @endforeach
        </select>
        @if ($deliveryChallan)<input type="hidden" name="sales_order_id" value="{{ $deliveryChallan->sales_order_id }}">@endif
    </div>
    @if ($deliveryChallan)
    <div class="col-md-2">
        <label class="form-label">Dispatch Value</label>
        <input type="text" class="form-control" value="{{ number_format((float) $deliveryChallan->dispatch_value, 2) }}" readonly>
    </div>
    <div class="col-md-2">
        <label class="form-label">E-way Required</label>
        <input type="text" class="form-control" value="{{ $deliveryChallan->eway_required ? 'Yes' : 'No' }}" readonly>
    </div>
    @endif
    <div class="col-md-3">
        <label class="form-label">Transport Mode *</label>
        <select name="transport_mode" id="transportMode" class="form-select" {{ $isDraft ? '' : 'disabled' }} required>
            @foreach ($transportModes as $mode)
                <option value="{{ $mode->value }}" @selected(old('transport_mode', $deliveryChallan?->transport_mode?->value ?? 'road') === $mode->value)>{{ $mode->label() }}</option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="transport_mode" value="{{ $deliveryChallan->transport_mode->value }}">@endunless
    </div>
    <div class="col-md-3">
        <label class="form-label vehicle-label">Vehicle No <span class="vehicle-required text-danger d-none">*</span></label>
        <input type="text" class="form-control text-uppercase" id="vehicleNumber" name="vehicle_number" value="{{ old('vehicle_number', $deliveryChallan?->vehicle_number) }}" {{ $isDraft ? '' : 'readonly' }} placeholder="GJ01AB1234" maxlength="20">
    </div>
    <div class="col-md-3">
        <label class="form-label">Transporter</label>
        <select name="transporter_id" id="transporterId" class="form-select select2" {{ $isDraft ? '' : 'disabled' }}>
            <option value="">Select transporter</option>
            @foreach ($transporters as $transporter)
                <option value="{{ $transporter->id }}" data-gstin="{{ $transporter->gstin }}" @selected((string) old('transporter_id', $deliveryChallan?->transporter_id) === (string) $transporter->id)>
                    {{ $transporter->code }} — {{ $transporter->name }}
                </option>
            @endforeach
        </select>
        @unless ($isDraft)<input type="hidden" name="transporter_id" value="{{ $deliveryChallan->transporter_id }}">@endunless
    </div>
    <div class="col-md-3">
        <label class="form-label">Transporter GSTIN</label>
        <input type="text" class="form-control" id="transporterGstin" name="transporter_gstin" value="{{ old('transporter_gstin', $deliveryChallan?->transporter_gstin) }}" {{ $isDraft ? '' : 'readonly' }} maxlength="15" placeholder="24AABCU9603R1ZM">
    </div>
    <div class="col-md-3">
        <label class="form-label">LR Number</label>
        <input type="text" class="form-control" name="lr_number" value="{{ old('lr_number', $deliveryChallan?->lr_number) }}" {{ $isDraft ? '' : 'readonly' }} maxlength="30">
    </div>
    <div class="col-md-3">
        <label class="form-label">LR Date</label>
        <input type="date" class="form-control" name="lr_date" value="{{ old('lr_date', optional($deliveryChallan?->lr_date)->format('Y-m-d')) }}" {{ $isDraft ? '' : 'readonly' }}>
    </div>
    <div class="col-md-3">
        <label class="form-label">Distance (km)</label>
        <input type="number" class="form-control" name="distance_km" value="{{ old('distance_km', $deliveryChallan?->distance_km) }}" {{ $isDraft ? '' : 'readonly' }} min="1" max="4000" placeholder="1–4000">
    </div>
    <div class="col-md-3">
        <label class="form-label">Driver Name</label>
        <input type="text" class="form-control" name="driver_name" value="{{ old('driver_name', $deliveryChallan?->driver_name) }}" {{ $isDraft ? '' : 'readonly' }} maxlength="100">
    </div>
    <div class="col-md-3">
        <label class="form-label">Driver Mobile</label>
        <input type="text" class="form-control" name="driver_mobile" value="{{ old('driver_mobile', $deliveryChallan?->driver_mobile) }}" {{ $isDraft ? '' : 'readonly' }} maxlength="10" placeholder="10 digits">
    </div>
    <div class="col-md-2">
        <label class="form-label">Packages *</label>
        <input type="number" class="form-control" name="number_of_packages" value="{{ old('number_of_packages', $deliveryChallan?->number_of_packages ?? 1) }}" {{ $isDraft ? '' : 'readonly' }} min="1" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Gross Weight</label>
        <input type="number" step="0.001" class="form-control" name="gross_weight" value="{{ old('gross_weight', $deliveryChallan?->gross_weight) }}" {{ $isDraft ? '' : 'readonly' }} min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Net Weight</label>
        <input type="number" step="0.001" class="form-control" name="net_weight" value="{{ old('net_weight', $deliveryChallan?->net_weight) }}" {{ $isDraft ? '' : 'readonly' }} min="0">
    </div>
    <div class="col-md-3">
        <label class="form-label">E-way Bill No</label>
        <input type="text" class="form-control" name="eway_bill_number" value="{{ old('eway_bill_number', $deliveryChallan?->eway_bill_number) }}" {{ $isDraft ? '' : 'readonly' }} maxlength="12" placeholder="12 digits">
    </div>
    <div class="col-md-3">
        <label class="form-label">Expected Delivery</label>
        <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($deliveryChallan?->expected_delivery_date)->format('Y-m-d')) }}" {{ $isDraft ? '' : 'readonly' }}>
    </div>
    <div class="col-md-12">
        <label class="form-label">Remarks</label>
        <input type="text" class="form-control" name="remarks" value="{{ old('remarks', $deliveryChallan?->remarks) }}" {{ $isDraft ? '' : 'readonly' }}>
    </div>
</div>
<div class="mb-2"><h6 class="mb-0">Dispatch Lines</h6></div>
<div id="lineRows">
@forelse ($lines as $index => $line)
<div class="row g-2 mb-2 line-row">
    <input type="hidden" name="items[{{ $index }}][sales_order_item_id]" value="{{ $line['sales_order_item_id'] }}">
    <div class="col-md-3"><input type="text" class="form-control" value="{{ $line['item_label'] ?? '' }}" readonly></div>
    <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" class="form-control challan-qty" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" {{ $isDraft ? '' : 'readonly' }} required></div>
    @if (($line['tracking_type'] ?? 'none') === 'batch')
    <div class="col-md-2">
        @if ($isDraft)
        <input type="number" class="form-control" name="items[{{ $index }}][batch_id]" value="{{ $line['batch_id'] ?? '' }}" placeholder="Batch ID" min="1">
        @else
        <input type="text" class="form-control" value="{{ $line['batch_label'] ?: ($line['batch_id'] ?? '—') }}" readonly>
        @endif
    </div>
    @endif
    <div class="col-md-3"><input type="text" class="form-control" name="items[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="Description" {{ $isDraft ? '' : 'readonly' }}></div>
</div>
@empty
<p class="text-muted" id="emptyLinesHint">Select a sales order to load pending dispatch quantities.</p>
@endforelse
</div>
@if ($isDraft)
<div class="mt-3"><button class="btn btn-primary" type="submit">Save Draft</button><a href="{{ route('admin.delivery-challans.index') }}" class="btn btn-light">Cancel</a></div>
@endif
</form>
</div></div>
