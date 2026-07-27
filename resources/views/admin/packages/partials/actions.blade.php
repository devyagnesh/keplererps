<div class="hstack gap-2 fs-15">
    @can('package.view')
    <a href="{{ route('admin.packages.print', ['id' => $package->id]) }}" target="_blank" class="btn btn-icon btn-sm btn-primary-light" title="Print label"><i class="ri-printer-line"></i></a>
    @endcan
    @can('package.delete')
        @if ($package->status !== \App\Enums\PackageStatus::Dispatched && $package->status !== \App\Enums\PackageStatus::Cancelled)
        <button type="button" class="btn btn-icon btn-sm btn-danger-light btn-delete-master" data-url="{{ route('admin.packages.destroy', $package) }}" title="Cancel package"><i class="ri-close-circle-line"></i></button>
        @endif
    @endcan
</div>
