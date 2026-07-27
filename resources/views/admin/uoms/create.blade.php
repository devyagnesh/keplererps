@extends('admin.layouts.app')
@section('title', 'Add UOM')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add UOM</h1></div>
@include('admin.uoms._form', ['uom' => null, 'action' => route('admin.uoms.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/uom/form.js') }}"></script>
@endpush
