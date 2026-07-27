@extends('admin.layouts.app')
@section('title', 'Overdue Follow-ups')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Overdue Follow-ups</h1></div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered text-nowrap w-100"><thead><tr><th>Type</th><th>Document</th><th>Name</th><th>Due</th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
    <td>{{ $row['type'] }}</td>
    <td>{{ $row['document_no'] }}</td>
    <td>{{ $row['name'] }}</td>
    <td>{{ $row['next_follow_up_date'] }}</td>
</tr>
@empty
<tr><td colspan="4" class="text-muted">No overdue follow-ups.</td></tr>
@endforelse
</tbody></table>
</div></div></div>
@endsection
