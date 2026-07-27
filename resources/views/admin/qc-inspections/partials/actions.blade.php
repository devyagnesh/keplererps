<a href="{{ route('admin.qc-inspections.edit', $inspection) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($inspection->status === \App\Enums\InspectionStatus::Completed)
<a href="{{ route('admin.qc-inspections.coa', $inspection) }}" class="btn btn-sm btn-success-light" target="_blank">CoA</a>
@endif
@if ($inspection->status->isEditable())
@can('qc_inspection.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.qc-inspections.destroy', $inspection) }}">Delete</button>
@endcan
@endif
