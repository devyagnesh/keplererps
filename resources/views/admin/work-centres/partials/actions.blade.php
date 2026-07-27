<a href="{{ route('admin.work-centres.edit', $asset) }}" class="btn btn-sm btn-primary-light">Edit</a>
@can('work_centre.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.work-centres.destroy', $asset) }}">Delete</button>
@endcan
