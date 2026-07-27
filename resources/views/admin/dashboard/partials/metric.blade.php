{{-- Dashboard metric tile. Expects: label, value, caption, icon, tone, link (nullable). --}}
<div class="col-xxl-3 col-lg-6">
    <div class="card custom-card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <span class="d-block mb-1 text-muted">{{ $label }}</span>
                    <h5 class="fw-semibold mb-0">{{ $value }}</h5>
                </div>
                <span class="avatar avatar-md bg-{{ $tone }}-transparent"><i class="bx {{ $icon }} fs-18"></i></span>
            </div>
            <p class="text-muted fs-12 mb-0">{{ $caption }}</p>
            @if (! empty($link))
                <a href="{{ $link }}" class="btn btn-sm btn-{{ $tone }}-light mt-3">View</a>
            @endif
        </div>
    </div>
</div>
