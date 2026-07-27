@extends('admin.layouts.app')
@section('title', 'Dashboard Widgets')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Dashboard Widgets by Role</h1></div>
<div class="card custom-card"><div class="card-body">
@foreach ($roles as $role)
@php $selected = collect($packs->get($role->name)?->widget_keys ?? []); @endphp
<form class="border rounded p-3 mb-3 dashboard-widget-form" data-ajax="1" method="post" action="{{ route('admin.dashboard-widgets.save') }}">
@csrf
<input type="hidden" name="role_name" value="{{ $role->name }}">
<h6 class="fw-semibold mb-3">{{ $role->name }}</h6>
<div class="row gy-2">
@foreach ($widgetKeys as $key)
<div class="col-md-3">
<div class="form-check">
<input class="form-check-input" type="checkbox" name="widget_keys[]" value="{{ $key }}" id="widget-{{ $role->id }}-{{ $key }}" @checked($selected->contains($key))>
<label class="form-check-label text-capitalize" for="widget-{{ $role->id }}-{{ $key }}">{{ str_replace('_', ' ', $key) }}</label>
</div>
</div>
@endforeach
</div>
<div class="mt-3"><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
</form>
@endforeach
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
