@extends('admin.layouts.app')
@section('title', 'Add HSN / SAC')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add HSN / SAC</h1></div>
@include('admin.hsn-codes._form', ['hsnCode' => null, 'action' => route('admin.hsn-codes.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/hsn-code/form.js') }}"></script>
@endpush
