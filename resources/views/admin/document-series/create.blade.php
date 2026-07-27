@extends('admin.layouts.app')
@section('title', 'Add Document Series')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Add Document Series</h1></div>
@include('admin.document-series._form', ['series' => null, 'action' => route('admin.document-series.store'), 'method' => 'POST'])
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/document-series/form.js') }}"></script>
@endpush
