@extends('admin.layouts.app')
@section('title', 'Import Parties')
@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-semibold fs-18 mb-0">Import Customers / Suppliers</h1>
    <a href="{{ route('admin.parties.import.template') }}" class="btn btn-light btn-sm">Download CSV Template</a>
</div>
<div class="card custom-card"><div class="card-body">
    <p class="text-muted">Upload an Excel-compatible CSV using the template columns. A dry-run preview lists invalid rows before anything is written.</p>
    <form id="partyImportForm" action="{{ route('admin.parties.import.preview') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="row gy-3">
            <div class="col-md-6">
                <label class="form-label">CSV File *</label>
                <input type="file" class="form-control" name="file" accept=".csv,text/csv" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Upload &amp; Preview</button>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>
    </form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/party/import.js') }}"></script>
@endpush
