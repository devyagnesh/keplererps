<a href="{{ route('admin.production-entries.edit', $entry) }}" class="btn btn-sm btn-primary-light">Open</a>
@if (! $entry->posted_at)
@can('production_entry.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.production-entries.destroy', $entry) }}">Delete</button>
@endcan
@endif
