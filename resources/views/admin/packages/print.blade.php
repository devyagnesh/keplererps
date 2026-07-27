@extends('admin.print.layout')

@section('title', 'Package Labels')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <div class="doc-title">Package Labels</div>
        <div>{{ config('app.name', 'Kepler ERP') }}</div>
    </div>
    <div class="text-end">
        @if ($packages->first()?->deliveryChallan)
            <div><strong>Challan:</strong> {{ $packages->first()->deliveryChallan->document_no }}</div>
        @endif
        <div>{{ $packages->count() }} label(s)</div>
    </div>
</div>

<div class="label-grid">
    @foreach ($packages as $package)
        <div class="label-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="label-no">{{ $package->label_no }}</div>
                    <div class="label-meta">
                        <div><strong>{{ $package->item?->item_code }}</strong> — {{ $package->item?->item_name }}</div>
                        <div>Pack: {{ $package->packingUnit?->code }} · Qty: {{ rtrim(rtrim(number_format((float) $package->quantity, 4, '.', ''), '0'), '.') }} {{ $package->packingUnit?->uom?->code }}</div>
                        <div>Batch: {{ $package->batch?->batch_no ?? '—' }}
                            @if ($package->batch?->expiry_date)
                                · Exp: {{ $package->batch->expiry_date->format('d M Y') }}
                            @endif
                        </div>
                        <div>Packed: {{ $package->packed_at?->format('d M Y H:i') }}</div>
                    </div>
                </div>
                <div class="label-qr" data-qr-payload="{{ $package->qr_payload }}" aria-label="QR code"></div>
            </div>
            <div class="label-payload">{{ $package->qr_payload }}</div>
        </div>
    @endforeach
</div>

<p class="mt-3 fs-12 text-muted d-print-none">
    Gate scanners can read the QR code or the payload line; the label number can also be typed into the gate scan screen.
</p>
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/lib/qrcode/qrcode.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/admin/packages/print-qr.js') }}"></script>
@endpush
