@props(['module' => null])

@php
    $routeName = request()->route()?->getName() ?? '';
    $key = $module;

    if ($key === null && str_starts_with($routeName, 'admin.')) {
        $withoutAdmin = substr($routeName, 6);

        // Registers: admin.reports.show + {register} parameter
        if ($withoutAdmin === 'reports.show') {
            $register = request()->route('register');
            if (is_string($register) && $register !== '') {
                $withoutAdmin = 'reports.'.$register;
            }
        }

        $candidates = [
            $withoutAdmin,
            preg_replace('/\.(index|create|edit|show|data|store|update|destroy)$/', '', $withoutAdmin) ?: $withoutAdmin,
        ];

        foreach (array_unique(array_filter($candidates)) as $candidate) {
            if (\Illuminate\Support\Facades\Lang::has('modules.'.$candidate)) {
                $key = $candidate;
                break;
            }
        }

        if ($key === null) {
            $base = explode('.', $withoutAdmin)[0] ?? '';
            if ($base !== '' && \Illuminate\Support\Facades\Lang::has('modules.'.$base)) {
                $key = $base;
            }
        }
    }

    $text = null;
    if (is_string($key) && $key !== '' && \Illuminate\Support\Facades\Lang::has('modules.'.$key)) {
        $text = __('modules.'.$key);
    }
@endphp
@if (is_string($text) && $text !== '')
    <p class="text-muted mb-0 fs-12 mt-1">{{ $text }}</p>
@endif
