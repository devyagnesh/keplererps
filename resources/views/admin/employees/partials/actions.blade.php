<div class="hstack gap-2 fs-15">
    @can('employee.update')
    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-icon btn-sm btn-primary-light" title="Edit"><i class="ri-pencil-line"></i></a>
    @endcan
    @can('employee.delete')
    <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.employees.destroy', $employee) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
    @endcan
</div>
