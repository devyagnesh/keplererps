@extends('admin.layouts.app')
@section('title', 'Price Lists')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Price Lists</h1>
    <a href="{{ route('admin.price-lists.create') }}" class="btn btn-primary btn-sm">Add</a>
</div>
<div class="card custom-card"><div class="card-body"><div class="table-responsive">
<table class="table table-bordered text-nowrap w-100"><thead><tr><th>Code</th><th>Name</th><th>Items</th><th>Default</th><th>Active</th><th></th></tr></thead>
<tbody>
@foreach($lists as $list)
<tr>
    <td>{{ $list->code }}</td>
    <td>{{ $list->name }}</td>
    <td>{{ $list->items_count }}</td>
    <td>{{ $list->is_default ? 'Yes' : 'No' }}</td>
    <td>{{ $list->is_active ? 'Yes' : 'No' }}</td>
    <td><a href="{{ route('admin.price-lists.edit', $list) }}" class="btn btn-sm btn-primary-light">Edit</a></td>
</tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection
