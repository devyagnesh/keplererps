@extends('admin.layouts.app')
@section('title', 'Stock Takes')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Stock Takes</h1>
    <a href="{{ route('admin.stock-takes.create') }}" class="btn btn-primary btn-sm">New Count</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered text-nowrap w-100"><thead><tr><th>Document</th><th>Date</th><th>Warehouse</th><th>Status</th><th></th></tr></thead>
<tbody>
@foreach($takes as $take)
<tr>
    <td>{{ $take->document_no }}</td>
    <td>{{ $take->document_date?->format('d M Y') }}</td>
    <td>{{ $take->warehouse?->code }}</td>
    <td>{{ $take->status }}</td>
    <td>@if($take->status === 'draft')<a href="{{ route('admin.stock-takes.edit', $take) }}" class="btn btn-sm btn-primary-light">Open</a>@endif</td>
</tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
