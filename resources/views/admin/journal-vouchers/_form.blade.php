@php
    $isEditable = ! $voucher || ($voucher->status->value === 'draft' && $voucher->source_type === null);
    $lines = old('lines', $voucher?->lines?->map(fn ($line) => [
        'ledger_account_id' => $line->ledger_account_id,
        'party_id' => $line->party_id,
        'debit' => (float) $line->debit,
        'credit' => (float) $line->credit,
        'narration' => $line->narration,
    ])->toArray() ?? [
        ['ledger_account_id' => '', 'party_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''],
        ['ledger_account_id' => '', 'party_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''],
    ]);
@endphp
<div class="card custom-card"><div class="card-body">
<form id="journalVoucherForm" action="{{ $action }}" method="POST" novalidate>
@csrf
<input type="hidden" name="_method" value="{{ $method }}">
<div class="row gy-3 mb-3">
    <div class="col-md-3"><label class="form-label">Voucher Date *</label>
        <input type="date" class="form-control" name="document_date" value="{{ old('document_date', optional($voucher?->document_date)->format('Y-m-d') ?? now()->toDateString()) }}" {{ $isEditable ? '' : 'readonly' }} required>
    </div>
    <div class="col-md-3"><label class="form-label">Voucher Type *</label>
        <select class="form-select" name="voucher_type" {{ $voucher ? 'disabled' : '' }} required>
            @foreach (\App\Enums\VoucherType::cases() as $type)
                @continue($type->isSystemGenerated())
                <option value="{{ $type->value }}" @selected(old('voucher_type', $voucher?->voucher_type?->value ?? 'journal') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @if ($voucher)<input type="hidden" name="voucher_type" value="{{ $voucher->voucher_type->value }}">@endif
    </div>
    <div class="col-md-3"><label class="form-label">Reference No</label>
        <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no', $voucher?->reference_no) }}" {{ $isEditable ? '' : 'readonly' }}>
    </div>
    <div class="col-md-3"><label class="form-label">Narration</label>
        <input type="text" class="form-control" name="narration" value="{{ old('narration', $voucher?->narration) }}" {{ $isEditable ? '' : 'readonly' }}>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Voucher Lines</h6>
    @if ($isEditable)
    <button type="button" class="btn btn-sm btn-primary-light" id="btnAddLine">Add Line</button>
    @endif
</div>
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead><tr><th style="width:32%">Account *</th><th style="width:22%">Party</th><th>Debit</th><th>Credit</th><th>Line Narration</th></tr></thead>
<tbody id="lineRows">
@foreach ($lines as $index => $line)
<tr class="line-row">
    <td>
        <select class="form-select" name="lines[{{ $index }}][ledger_account_id]" {{ $isEditable ? '' : 'disabled' }}>
            <option value="">Select account</option>
            @foreach ($accounts as $option)
                <option value="{{ $option->id }}" @selected((string) ($line['ledger_account_id'] ?? '') === (string) $option->id)>
                    {{ $option->code }} — {{ $option->name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select" name="lines[{{ $index }}][party_id]" {{ $isEditable ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($parties as $party)
                <option value="{{ $party->id }}" @selected((string) ($line['party_id'] ?? '') === (string) $party->id)>
                    {{ $party->party_code }} — {{ $party->party_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td><input type="number" step="0.01" min="0" class="form-control line-debit" name="lines[{{ $index }}][debit]" value="{{ $line['debit'] ?? '' }}" {{ $isEditable ? '' : 'readonly' }}></td>
    <td><input type="number" step="0.01" min="0" class="form-control line-credit" name="lines[{{ $index }}][credit]" value="{{ $line['credit'] ?? '' }}" {{ $isEditable ? '' : 'readonly' }}></td>
    <td><input type="text" class="form-control" name="lines[{{ $index }}][narration]" value="{{ $line['narration'] ?? '' }}" {{ $isEditable ? '' : 'readonly' }}></td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr>
    <th colspan="2" class="text-end">Totals</th>
    <th id="totalDebit">0.00</th>
    <th id="totalCredit">0.00</th>
    <th id="balanceHint" class="text-muted"></th>
</tr>
</tfoot>
</table>
</div>
@if ($isEditable)
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Draft</button>
    <a href="{{ route('admin.journal-vouchers.index') }}" class="btn btn-light">Cancel</a>
</div>
@endif
</form>
</div></div>
@if ($voucher)
<div class="card custom-card"><div class="card-body row gy-2">
    <div class="col-md-4"><span class="text-muted d-block fs-12">Total Debit</span><strong>{{ number_format((float) $voucher->total_debit, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Total Credit</span><strong>{{ number_format((float) $voucher->total_credit, 2) }}</strong></div>
    <div class="col-md-4"><span class="text-muted d-block fs-12">Posted At</span><strong>{{ $voucher->posted_at?->format('d M Y H:i') ?? '—' }}</strong></div>
</div></div>
@endif
