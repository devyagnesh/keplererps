@extends('admin.layouts.app')
@section('title', 'Salary Run '.$run->document_no)
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $run->document_no }}</h1>
        <p class="text-muted mb-0">
            {{ $run->periodLabel() }} · Paid {{ $run->payment_date?->format('d M Y') }} ·
            <span class="badge {{ $run->status->badgeClass() }}">{{ $run->status->label() }}</span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.salary-runs.print', $run) }}" target="_blank" class="btn btn-primary-light btn-sm">Payslips</a>
        @can('salary_run.update')
            @if ($run->status->isEditable())
            <button type="button" id="btnRecalculate" class="btn btn-light btn-sm" data-url="{{ route('admin.salary-runs.recalculate', $run) }}">Recalculate</button>
            @endif
        @endcan
        @can('salary_run.post')
            @if ($run->status->isEditable())
            <button type="button" id="btnPostRun" class="btn btn-primary btn-sm" data-url="{{ route('admin.salary-runs.post', $run) }}">Post to Ledger</button>
            @endif
        @endcan
        @can('salary_run.update')
            @if ($run->status !== \App\Enums\SalaryRunStatus::Cancelled)
            <button type="button" id="btnCancelRun" class="btn btn-danger-light btn-sm" data-url="{{ route('admin.salary-runs.cancel', $run) }}">Cancel Run</button>
            @endif
        @endcan
        <a href="{{ route('admin.salary-runs.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>

<div class="row">
    @foreach ([
        ['label' => 'Employees', 'value' => number_format($run->employee_count)],
        ['label' => 'Gross', 'value' => number_format((float) $run->gross_total, 2)],
        ['label' => 'Deductions', 'value' => number_format((float) $run->deduction_total, 2)],
        ['label' => 'Net Payable', 'value' => number_format((float) $run->net_total, 2)],
    ] as $tile)
    <div class="col-xxl-3 col-lg-6">
        <div class="card custom-card"><div class="card-body">
            <span class="d-block mb-1 text-muted">{{ $tile['label'] }}</span>
            <h5 class="fw-semibold mb-0">{{ $tile['value'] }}</h5>
        </div></div>
    </div>
    @endforeach
</div>

<div class="card custom-card">
<div class="card-header"><div class="card-title">Salary Slips</div></div>
<div class="card-body table-responsive">
<table class="table table-bordered text-nowrap w-100">
    <thead><tr><th>Code</th><th>Employee</th><th>Department</th><th>Payable Days</th><th>OT Hrs</th><th>Basic</th><th>Allowances</th><th>Overtime</th><th>Gross</th><th>Deductions</th><th>Net</th></tr></thead>
    <tbody>
    @forelse ($run->slips as $slip)
        <tr>
            <td>{{ $slip->employee?->employee_code }}</td>
            <td>{{ $slip->employee?->full_name }}</td>
            <td>{{ $slip->employee?->department ?? '—' }}</td>
            <td>{{ number_format((float) $slip->payable_days, 2) }} / {{ $slip->period_days }}</td>
            <td>{{ number_format((float) $slip->overtime_hours, 2) }}</td>
            <td>{{ number_format((float) $slip->basic_amount, 2) }}</td>
            <td>{{ number_format((float) $slip->allowance_amount, 2) }}</td>
            <td>{{ number_format((float) $slip->overtime_amount, 2) }}</td>
            <td>{{ number_format((float) $slip->gross_amount, 2) }}</td>
            <td>{{ number_format((float) $slip->deduction_amount, 2) }}</td>
            <td class="fw-semibold">{{ number_format((float) $slip->net_amount, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="11" class="text-muted">No slips in this run.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/salary-runs/form.js') }}"></script>
@endpush
