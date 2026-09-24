<div class="menu-title">My Space</div>
<ul class="nav flex-column">
    @foreach ([
        ['portal.dashboard', 'house', 'My Dashboard', 'portal.dashboard'],
        ['portal.attendance', 'calendar3', 'My Attendance', 'portal.attendance'],
        ['portal.leaves.index', 'calendar-plus', 'My Leaves', 'portal.leaves.*'],
        ['portal.corrections.index', 'pencil', 'Attendance Corrections', 'portal.corrections.*'],
        ['portal.payslips.index', 'receipt', 'My Payslips', 'portal.payslips.*'],
    ] as [$route, $icon, $label, $pattern])
        <li class="nav-item">
            <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
            </a>
        </li>
    @endforeach
</ul>
