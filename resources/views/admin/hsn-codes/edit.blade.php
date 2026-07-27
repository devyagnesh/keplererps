@extends('admin.layouts.app')
@section('title', 'Edit HSN / SAC')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Edit HSN / SAC</h1></div>
@include('admin.hsn-codes._form', ['hsnCode' => $hsnCode, 'action' => route('admin.hsn-codes.update', $hsnCode), 'method' => 'PUT'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/hsn-code/form.js') }}"></script>
@endpush
