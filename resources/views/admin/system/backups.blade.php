@extends('admin.layouts.app')
@section('title', 'Backups')
@section('content')
<div class="my-4 page-header-breadcrumb d-flex justify-content-between">
    <h1 class="page-title fw-semibold fs-18 mb-0">Backups</h1>
    <form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary" type="submit">Create Backup</button></form>
</div>
<div class="card custom-card"><div class="card-body table-responsive">
<table class="table table-bordered"><thead><tr><th>When</th><th>Size</th><th>Status</th><th>Notes</th><th></th></tr></thead>
<tbody>
@forelse($backups as $backup)
<tr>
<td>{{ $backup->created_at }}</td>
<td>{{ number_format($backup->size_bytes / 1024, 1) }} KB</td>
<td>{{ $backup->status }}</td>
<td>{{ $backup->notes }}</td>
<td class="text-nowrap">
@if($backup->status === 'ready')
<a class="btn btn-sm btn-primary-light" href="{{ route('admin.backups.download', $backup) }}">Download</a>
@can('backup.update')
<button type="button" class="btn btn-sm btn-warning-light" data-bs-toggle="modal" data-bs-target="#restoreModal{{ $backup->id }}">Restore</button>
<div class="modal fade" id="restoreModal{{ $backup->id }}" tabindex="-1" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Restore Backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form data-ajax="1" data-reload="1" method="post" action="{{ route('admin.backups.restore', $backup) }}">
@csrf
<div class="modal-body">
<p class="text-muted">This will overwrite files under <code>storage/app</code> (except backups). Type the company legal name to confirm.</p>
<label class="form-label">Confirmation</label>
<input type="text" class="form-control" name="confirmation" required autocomplete="off">
</div>
<div class="modal-footer">
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-warning">Restore</button>
</div>
</form>
</div></div></div>
@endcan
@endif
</td>
</tr>
@empty
<tr><td colspan="5" class="text-muted">No backups yet.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/admin/js/admin/gap-close/forms.js') }}"></script>
@endpush
