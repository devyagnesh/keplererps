<a href="{{ route('admin.boms.edit', $bom) }}" class="btn btn-sm btn-primary-light">Edit</a>
@can('bom.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.boms.destroy', $bom) }}">Delete</button>
@endcan
