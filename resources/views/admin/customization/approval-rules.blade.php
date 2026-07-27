@extends('admin.layouts.app')
@section('title', 'Approval Rules')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Approval Rules</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form id="approvalRuleForm" data-ajax="1" data-reload="1" method="post" action="{{ route('admin.approval-rules.store') }}">
    @csrf
    <div class="row g-2">
        <div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
        <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-md-2"><input name="document_type" class="form-control" placeholder="sales_order / purchase_order" required></div>
        <div class="col-md-2"><input name="condition_value" type="number" step="0.01" class="form-control" placeholder="Threshold" required></div>
        <div class="col-md-3"><input name="approver_permission" class="form-control" placeholder="First step permission" required></div>
        <div class="col-md-3"><input name="steps" class="form-control" placeholder="Steps: perm1,perm2 (optional)"></div>
        <div class="col-md-2">
            <select name="approval_mode" class="form-select">
                <option value="sequential">Sequential</option>
                <option value="parallel">Parallel</option>
            </select>
        </div>
        <div class="col-md-2"><input name="escalation_hours" type="number" min="1" class="form-control" placeholder="Escalate hrs"></div>
        <div class="col-md-2"><input name="auto_approve_below" type="number" step="0.01" class="form-control" placeholder="Auto below"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered"><thead><tr><th>Code</th><th>Name</th><th>Document</th><th>When</th><th>Mode</th><th>Steps</th><th>Escalate</th></tr></thead>
<tbody>
@foreach($rules as $rule)
<tr>
    <td>{{ $rule->code }}</td>
    <td>{{ $rule->name }}</td>
    <td>{{ $rule->document_type }}</td>
    <td>{{ $rule->condition_field }} {{ $rule->condition_operator }} {{ $rule->condition_value }}</td>
    <td>{{ $rule->approval_mode }}</td>
    <td class="fs-12">{{ collect($rule->normalizedSteps())->pluck('permission')->implode(' → ') }}</td>
    <td>{{ $rule->escalation_hours ? $rule->escalation_hours.'h' : '—' }}</td>
</tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
