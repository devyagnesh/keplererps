@extends('admin.print.layout')

@section('title', 'Payslips '.$run->document_no)

@section('content')
@foreach ($run->slips as $slip)
<div class="mb-4" style="page-break-inside: avoid;">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <div class="doc-title">Payslip</div>
            <div>{{ config('app.name', 'Kepler ERP') }}</div>
        </div>
        <div class="text-end">
            <div><strong>{{ $run->periodLabel() }}</strong></div>
            <div>Run {{ $run->document_no }}</div>
            <div>Paid {{ $run->payment_date?->format('d M Y') }}</div>
        </div>
    </div>

    <table class="lines mb-2">
        <tbody>
            <tr>
                <th style="width:18%">Employee</th>
                <td style="width:32%">{{ $slip->employee?->employee_code }} — {{ $slip->employee?->full_name }}</td>
                <th style="width:18%">Designation</th>
                <td>{{ $slip->employee?->designation ?? '—' }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $slip->employee?->department ?? '—' }}</td>
                <th>Payable Days</th>
                <td>{{ number_format((float) $slip->payable_days, 2) }} of {{ $slip->period_days }}</td>
            </tr>
            <tr>
                <th>UAN / PF</th>
                <td>{{ $slip->employee?->uan ?? '—' }} / {{ $slip->employee?->pf_number ?? '—' }}</td>
                <th>ESI</th>
                <td>{{ $slip->employee?->esi_number ?? '—' }}</td>
            </tr>
            <tr>
                <th>PAN</th>
                <td>{{ $slip->employee?->pan ?? '—' }}</td>
                <th>Bank</th>
                <td>{{ $slip->employee?->bank_account_no ?? '—' }}
                    @if ($slip->employee?->ifsc_code)
                        ({{ $slip->employee->ifsc_code }})
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <table class="lines">
        <thead><tr><th>Earnings</th><th class="text-end">Amount</th><th>Deductions</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
            <tr>
                <td>Basic Pay</td>
                <td class="text-end">{{ number_format((float) $slip->basic_amount, 2) }}</td>
                <td>Fixed Deduction</td>
                <td class="text-end">{{ number_format((float) $slip->deduction_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Allowances</td>
                <td class="text-end">{{ number_format((float) $slip->allowance_amount, 2) }}</td>
                <td></td>
                <td class="text-end"></td>
            </tr>
            <tr>
                <td>Overtime ({{ number_format((float) $slip->overtime_hours, 2) }} hrs)</td>
                <td class="text-end">{{ number_format((float) $slip->overtime_amount, 2) }}</td>
                <td></td>
                <td class="text-end"></td>
            </tr>
            <tr>
                <th>Gross Earnings</th>
                <th class="text-end">{{ number_format((float) $slip->gross_amount, 2) }}</th>
                <th>Total Deductions</th>
                <th class="text-end">{{ number_format((float) $slip->deduction_amount, 2) }}</th>
            </tr>
            <tr>
                <th colspan="3">Net Pay</th>
                <th class="text-end">{{ number_format((float) $slip->net_amount, 2) }}</th>
            </tr>
        </tbody>
    </table>

    <p class="mt-2 fs-12">Computer-generated payslip; no signature required.</p>
</div>
@endforeach
@endsection
