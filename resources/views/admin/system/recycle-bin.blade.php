@extends('admin.layouts.app')
@section('title', 'Recycle Bin')
@section('content')
<div class="my-4 page-header-breadcrumb"><h1 class="page-title fw-semibold fs-18 mb-0">Recycle Bin</h1></div>
<div class="card custom-card mb-3"><div class="card-body">
<form method="get" class="row g-2">
<div class="col-md-4">
<select name="type" class="form-select" onchange="this.form.submit()">
<option value="">All types</option>
@foreach($types as $t)
<option value="{{ $t }}" @selected($type === $t)>{{ str_replace('_',' ', ucfirst($t)) }}</option>
@endforeach
</select>
</div>
</form>
</div></div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered"><thead><tr><th>Type</th><th>Label</th><th>Deleted</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
<td>{{ $row['type'] }}</td>
<td>{{ $row['label'] }}</td>
<td>{{ $row['deleted_at'] }}</td>
<td>
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.recycle-bin.restore') }}">
@csrf
<input type="hidden" name="type" value="{{ $row['type'] }}">
<input type="hidden" name="id" value="{{ $row['id'] }}">
<button class="btn btn-sm btn-success" type="submit">Restore</button>
</form>
</td>
</tr>
@empty
<tr><td colspan="4" class="text-muted">Bin is empty.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
