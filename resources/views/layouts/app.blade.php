<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ setting('company_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
@php($user = auth()->user())
<div class="d-flex">
    <nav id="sidebar" class="sidebar bg-dark text-white d-none d-lg-flex flex-column">
        <a href="{{ route('home') }}" class="brand d-flex align-items-center text-white text-decoration-none">
            @if(setting('company_logo'))
                <img src="{{ asset('storage/'.setting('company_logo')) }}" alt="" class="me-2" height="30">
            @else
                <i class="bi bi-fingerprint fs-4 me-2"></i>
            @endif
            <span class="fw-semibold text-truncate">{{ setting('company_name') }}</span>
        </a>
        <div class="flex-grow-1 overflow-auto pb-3">
            @if($user->isStaff() || $user->isManager())
                @include('layouts.partials.admin-menu')
            @endif
            @if($user->employee)
                @include('layouts.partials.portal-menu')
            @endif
        </div>
    </nav>

    <div class="flex-grow-1 min-vh-100 d-flex flex-column main">
        <header class="navbar bg-white border-bottom px-3">
            <button class="btn btn-outline-secondary d-lg-none" type="button" onclick="document.getElementById('sidebar').classList.toggle('d-none')">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="h5 mb-0">@yield('title', 'Dashboard')</h1>
            <div class="d-flex align-items-center gap-2">
            @php($unread = $user->unreadNotifications()->limit(8)->get())
            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown" title="Notifications">
                    <i class="bi bi-bell"></i>
                    @if ($count = $user->unreadNotifications()->count())
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ $count }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px">
                    <div class="list-group list-group-flush">
                        @forelse ($unread as $notification)
                            <a href="{{ route('notifications.open', $notification->id) }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold small">{{ $notification->data['title'] }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'], 90) }}</div>
                                <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                        @empty
                            <div class="list-group-item small text-muted">No new notifications.</div>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action text-center small">View all</a>
                    </div>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> {{ $user->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ \App\Models\User::ROLES[$user->role] }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
            </div>
        </header>

        <main class="p-3 p-md-4 flex-grow-1">
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
</script>
@stack('scripts')
</body>
</html>
