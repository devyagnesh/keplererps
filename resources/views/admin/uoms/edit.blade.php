@extends('admin.layouts.app')
@section('title', 'Edit UOM')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit UOM</h1></div>
@include('admin.uoms._form', ['uom' => $uom, 'action' => route('admin.uoms.update', $uom), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/uom/form.js') }}"></script>
@endpush
