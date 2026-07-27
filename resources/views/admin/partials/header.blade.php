<header class="app-header">
    <div class="main-header-container container-fluid">
        <div class="header-content-left">
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ route('admin.dashboard') }}" class="header-logo">
                        <img src="{{ asset('assets/admin/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
                        <img src="{{ asset('assets/admin/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
                        <img src="{{ asset('assets/admin/images/brand-logos/desktop-dark.png') }}" alt="logo" class="desktop-dark">
                        <img src="{{ asset('assets/admin/images/brand-logos/toggle-dark.png') }}" alt="logo" class="toggle-dark">
                        <img src="{{ asset('assets/admin/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
                        <img src="{{ asset('assets/admin/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
                    </a>
                </div>
            </div>
            <div class="header-element">
                <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
            </div>
        </div>
        <div class="header-content-right">
            <div class="header-element">
                <form method="post" action="{{ route('admin.locale.update') }}" class="d-inline">
                    @csrf
                    <select name="locale" class="form-select form-select-sm" onchange="this.form.submit()" title="Language">
                        @foreach(['en' => 'EN', 'hi' => 'HI', 'gu' => 'GU'] as $code => $label)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="header-element">
                <a href="{{ route('admin.notifications.index') }}" class="header-link" title="Notifications">
                    <i class="bx bx-bell header-link-icon"></i>
                    @php $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0; @endphp
                    @if ($unreadNotifications > 0)
                        <span class="badge bg-danger rounded-pill header-icon-badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </a>
            </div>
            <div class="header-element">
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-sm-2 me-0">
                            <span class="avatar avatar-sm bg-primary-transparent rounded-circle">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                        </div>
                        <div class="d-sm-block d-none">
                            <p class="fw-semibold mb-0 lh-1">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <span class="op-7 fw-normal d-block fs-11">Administrator</span>
                        </div>
                    </div>
                </a>
                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                    <li>
                        <a class="dropdown-item d-flex" href="{{ route('admin.notifications.index') }}">
                            <i class="ti ti-bell fs-18 me-2 op-7"></i>My Notifications
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex" href="{{ route('admin.company.edit') }}">
                            <i class="ti ti-building fs-18 me-2 op-7"></i>Company Setup
                        </a>
                    </li>
                    <li>
                        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex border-0 bg-transparent w-100">
                                <i class="ti ti-logout fs-18 me-2 op-7"></i>Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
