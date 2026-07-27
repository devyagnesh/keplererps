<a href="{{ route('admin.production-plans.edit', $plan) }}" class="btn btn-sm btn-primary-light">Open</a>
@if ($plan->status->value === 'draft')
@can('production_plan.delete')
<button type="button" class="btn btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.production-plans.destroy', $plan) }}">Delete</button>
@endcan
@endif
