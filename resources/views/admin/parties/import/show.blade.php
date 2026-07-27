@extends('admin.layouts.app')
@section('title', 'Import Preview')
@section('content')
<div class="my-4"><h1 class="page-title fw-semibold fs-18 mb-0">Import Preview</h1></div>
<div class="card custom-card"><div class="card-body">
    <div class="row mb-3">
        <div class="col-md-3"><div class="p-3 border rounded"><div class="text-muted">Total</div><div class="fs-4 fw-semibold" id="statTotal">{{ $import->total_rows }}</div></div></div>
        <div class="col-md-3"><div class="p-3 border rounded"><div class="text-muted">Valid</div><div class="fs-4 fw-semibold text-success" id="statValid">{{ $import->valid_rows }}</div></div></div>
        <div class="col-md-3"><div class="p-3 border rounded"><div class="text-muted">Invalid</div><div class="fs-4 fw-semibold text-danger" id="statInvalid">{{ $import->invalid_rows }}</div></div></div>
        <div class="col-md-3"><div class="p-3 border rounded"><div class="text-muted">Status</div><div class="fs-4 fw-semibold" id="statStatus">{{ ucfirst($import->status) }}</div></div></div>
    </div>
    <p class="mb-2">File: <strong>{{ $import->original_filename }}</strong></p>
    @if($import->status === 'completed')
        <p class="mb-3">Imported <strong id="statImported">{{ $import->imported_rows }}</strong>, skipped <strong id="statSkipped">{{ $import->skipped_rows }}</strong>.</p>
        @if($import->error_file_path)
            <a href="{{ route('admin.parties.import.errors', $import) }}" class="btn btn-warning-light btn-sm mb-3">Download Error File</a>
        @endif
    @endif
    @if(!empty($import->preview_errors))
        <h6 class="fw-semibold">Preview errors (first {{ count($import->preview_errors) }})</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Row</th><th>Errors</th></tr></thead>
                <tbody>
                @foreach($import->preview_errors as $error)
                    <tr>
                        <td>{{ $error['row'] }}</td>
                        <td>{{ implode(' | ', $error['errors']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <div class="d-flex gap-2">
        @if($import->status === 'previewed' && $import->valid_rows > 0)
            <button type="button" class="btn btn-primary" id="commitImportBtn" data-url="{{ route('admin.parties.import.commit', $import) }}">Commit Valid Rows</button>
        @endif
        <a href="{{ route('admin.parties.import.index') }}" class="btn btn-light">New Import</a>
        <a href="{{ route('admin.parties.index') }}" class="btn btn-light">Parties</a>
    </div>
</div></div>
@endsection
@push('scripts')
<script>
    window.importStatusUrl = @json(route('admin.parties.import.status', $import));
    window.importStatus = @json($import->status);
</script>
<script src="{{ asset('assets/admin/js/admin/party/import-show.js') }}"></script>
@endpush
