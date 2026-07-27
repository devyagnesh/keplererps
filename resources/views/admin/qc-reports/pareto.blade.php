@extends('admin.layouts.app')
@section('title', 'Defect Pareto')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Defect Pareto</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form method="get" class="row g-2">
    <div class="col-md-3"><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
    <div class="col-md-3"><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered text-nowrap w-100"><thead><tr><th>Code</th><th>Defect</th><th>Count</th><th>Share %</th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['share'] }}</td></tr>
@empty
<tr><td colspan="4" class="text-muted">No defect data in range.</td></tr>
@endforelse
</tbody></table>
</div></div></div>
@endsection
