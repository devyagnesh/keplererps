<a href="{{ route('admin.qc-templates.edit', $template) }}" class="btn btn-sm btn-primary-light">Edit</a>
@can('qc_template.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.qc-templates.destroy', $template) }}">Delete</button>
@endcan
