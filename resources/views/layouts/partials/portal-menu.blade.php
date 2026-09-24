<div class="menu-title">My Space</div>
<ul class="nav flex-column">
    @foreach ([
        ['portal.dashboard', 'home-01', 'My Dashboard', 'portal.dashboard'],
        ['portal.attendance', 'calendar-04', 'My Attendance', 'portal.attendance'],
        ['portal.leaves.index', 'calendar-add-01', 'My Leaves', 'portal.leaves.*'],
        ['portal.corrections.index', 'edit-02', 'Attendance Corrections', 'portal.corrections.*'],
        ['portal.payslips.index', 'invoice-03', 'My Payslips', 'portal.payslips.*'],
        ['portal.documents.index', 'folder-open', 'My Documents', 'portal.documents.*'],
    ] as [$route, $icon, $label, $pattern])
        <li class="nav-item">
            <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                <i class="hgi-stroke hgi-{{ $icon }} me-2"></i>{{ $label }}
            </a>
        </li>
    @endforeach
</ul>
