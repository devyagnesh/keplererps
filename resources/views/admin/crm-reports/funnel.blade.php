@extends('admin.layouts.app')
@section('title', 'CRM Funnel')
@section('content')
<div class="my-4 page-header-breadcrumb"><div><h1 class="page-title fw-semibold fs-18 mb-0">CRM Funnel</h1><x-admin.module-intro /></div></div>
<div class="card custom-card mb-3"><div class="card-body">
<form method="get" class="row g-2">
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>
<p class="mt-3 mb-0">Conversion rate: <strong>{{ $summary['conversion_rate'] }}%</strong></p>
</div></div>
<div class="row">
    <div class="col-md-6"><div class="card custom-card"><div class="card-body">
        <h6 class="fw-semibold">Leads</h6>
        <table class="table table-sm"><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody>
        @foreach($summary['leads'] as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>@endforeach
        </tbody></table>
    </div></div></div>
    <div class="col-md-6"><div class="card custom-card"><div class="card-body">
        <h6 class="fw-semibold">Opportunities</h6>
        <table class="table table-sm"><thead><tr><th>Stage</th><th>Total</th></tr></thead><tbody>
        @foreach($summary['opportunities'] as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>@endforeach
        </tbody></table>
    </div></div></div>
</div>
@endsection
