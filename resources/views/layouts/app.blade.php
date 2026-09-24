<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ setting('company_name') }}</title>
    <script>
        try {
            var theme = localStorage.getItem('zk-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=2" rel="stylesheet">
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $unread = $user->unreadNotifications()->limit(8)->get();
    $unreadCount = $user->unreadNotifications()->count();
@endphp
<div class="app-shell">
    <nav id="sidebar" class="sidebar">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">
                @if (setting('company_logo'))
                    <img src="{{ asset('storage/'.setting('company_logo')) }}" alt="">
                @else
                    <i class="hgi-stroke hgi-fingerprint-scan"></i>
                @endif
            </span>
            <span class="min-w-0">
                <span class="brand-name d-block text-truncate">{{ setting('company_name') }}</span>
                <span class="brand-sub">Attendance &amp; Payroll</span>
            </span>
        </a>
        <div class="sidebar-scroll">
            @if ($user->isStaff() || $user->isManager())
                @include('layouts.partials.admin-menu')
            @endif
            @if ($user->employee)
                @include('layouts.partials.portal-menu')
            @endif
        </div>
        <div class="sidebar-footer d-flex align-items-center gap-2">
            <span class="avatar avatar-sm">{{ $initials }}</span>
            <span class="min-w-0">
                <span class="d-block text-white text-truncate">{{ $user->name }}</span>
                <span>{{ \App\Models\User::ROLES[$user->role] }}</span>
            </span>
        </div>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn d-lg-none" type="button" id="sidebarToggle" aria-label="Open menu">
                <i class="hgi-stroke hgi-menu-01"></i>
            </button>
            <div class="me-auto min-w-0">
                <div class="crumb">{{ setting('company_name') }}</div>
                <h1 class="text-truncate">@yield('title', 'Dashboard')</h1>
            </div>

            <button class="icon-btn" type="button" id="themeToggle" aria-label="Switch light or dark mode" title="Switch light or dark mode">
                <i class="hgi-stroke hgi-moon-02" data-theme-icon></i>
            </button>

            <div class="dropdown">
                <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Notifications" title="Notifications">
                    <i class="hgi-stroke hgi-notification-03"></i>
                    @if ($unreadCount)
                        <span class="dot">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Notifications</span>
                        @if ($unreadCount)<span class="badge text-bg-primary">{{ $unreadCount }} new</span>@endif
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto">
                        @forelse ($unread as $notification)
                            <a href="{{ route('notifications.open', $notification->id) }}" class="list-group-item list-group-item-action d-flex gap-2 py-2">
                                <span class="stat-icon tone-indigo" style="width: 32px; height: 32px; font-size: .9rem"><i class="hgi-stroke hgi-notification-03"></i></span>
                                <span class="min-w-0">
                                    <span class="d-block fw-semibold small">{{ $notification->data['title'] }}</span>
                                    <span class="d-block small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'], 90) }}</span>
                                    <span class="d-block small text-muted">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="empty-state py-4"><i class="hgi-stroke hgi-notification-off-03"></i>You are all caught up.</div>
                        @endforelse
                    </div>
                    <a href="{{ route('notifications.index') }}" class="d-block text-center small py-2 border-top text-decoration-none">View all notifications</a>
                </div>
            </div>

            <div class="dropdown">
                <button class="user-chip" data-bs-toggle="dropdown">
                    <span class="avatar avatar-sm">{{ $initials }}</span>
                    <span class="d-none d-md-block text-start">
                        <span class="d-block fw-semibold small">{{ $user->name }}</span>
                        <small>{{ \App\Models\User::ROLES[$user->role] }}</small>
                    </span>
                    <i class="hgi-stroke hgi-arrow-down-01 small text-muted d-none d-md-inline"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="hgi-stroke hgi-user me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="hgi-stroke hgi-notification-03 me-2"></i>Notifications</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="hgi-stroke hgi-logout-03 me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="content">
            @include('layouts.partials.alerts')
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    (function () {
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var toggle = document.getElementById('sidebarToggle');
        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            backdrop.classList.toggle('open', open);
        }
        if (toggle) toggle.addEventListener('click', function () { setOpen(!sidebar.classList.contains('open')); });
        backdrop.addEventListener('click', function () { setOpen(false); });

        var themeButton = document.getElementById('themeToggle');
        var icon = themeButton.querySelector('[data-theme-icon]');
        function paintIcon() {
            var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            icon.className = dark ? 'hgi-stroke hgi-sun-03' : 'hgi-stroke hgi-moon-02';
        }
        paintIcon();
        themeButton.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('zk-theme', next); } catch (e) {}
            paintIcon();
            document.dispatchEvent(new CustomEvent('zk:theme', { detail: next }));
        });
    })();
</script>
@stack('scripts')
</body>
</html>
