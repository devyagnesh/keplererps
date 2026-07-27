<div class="hstack gap-2 fs-15">
    @can('salary_run.view')
    <a href="{{ route('admin.salary-runs.edit', $run) }}" class="btn btn-icon btn-sm btn-primary-light" title="Open"><i class="ri-eye-line"></i></a>
    <a href="{{ route('admin.salary-runs.print', $run) }}" target="_blank" class="btn btn-icon btn-sm btn-primary-light" title="Payslips"><i class="ri-printer-line"></i></a>
    @endcan
    @can('salary_run.delete')
        @if ($run->status === \App\Enums\SalaryRunStatus::Draft)
        <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.salary-runs.destroy', $run) }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
        @endif
    @endcan
</div>
