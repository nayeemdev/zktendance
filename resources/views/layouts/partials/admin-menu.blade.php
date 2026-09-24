@php
    $menu = [
        'Main' => [
            ['admin.dashboard', 'speedometer2', 'Dashboard', 'admin.dashboard'],
            ['admin.employees.index', 'people', 'Employees', 'admin.employees.*'],
        ],
        'Attendance' => [
            ['admin.attendance.index', 'calendar-check', 'Daily Attendance', 'admin.attendance.*'],
            ['admin.punches.index', 'fingerprint', 'Device Punches', 'admin.punches.*'],
            ['admin.corrections.index', 'pencil-square', 'Corrections', 'admin.corrections.*'],
            ['admin.overtime.index', 'hourglass-top', 'Overtime Approvals', 'admin.overtime.*'],
            ['admin.shifts.index', 'clock', 'Shifts', 'admin.shifts.*'],
            ['admin.roster.index', 'calendar-week', 'Shift Roster', 'admin.roster.*'],
            ['admin.holidays.index', 'calendar-event', 'Holidays', 'admin.holidays.*'],
        ],
        'Leave' => [
            ['admin.leaves.index', 'calendar-x', 'Leave Requests', 'admin.leaves.*'],
            ['admin.leave-balances.index', 'bar-chart', 'Leave Balances', 'admin.leave-balances.*'],
            ['admin.leave-types.index', 'tags', 'Leave Types', 'admin.leave-types.*'],
        ],
        'Payroll' => [
            ['admin.payroll.index', 'cash-stack', 'Payroll Runs', 'admin.payroll.*'],
            ['admin.salary-structures.index', 'diagram-3', 'Salary Structures', 'admin.salary-structures.*'],
            ['admin.salary-components.index', 'list-ul', 'Salary Components', 'admin.salary-components.*'],
            ['admin.overtime-rules.index', 'hourglass-split', 'Overtime Rules', 'admin.overtime-rules.*'],
            ['admin.adjustments.index', 'plus-slash-minus', 'Bonus & Deductions', 'admin.adjustments.*'],
            ['admin.loans.index', 'bank', 'Loans & Advances', 'admin.loans.*'],
        ],
        'Company' => [
            ['admin.reports.index', 'file-earmark-bar-graph', 'Reports', 'admin.reports.*'],
            ['admin.notices.index', 'megaphone', 'Notices', 'admin.notices.*'],
            ['admin.departments.index', 'diagram-2', 'Departments', 'admin.departments.*'],
            ['admin.designations.index', 'person-badge', 'Designations', 'admin.designations.*'],
        ],
    ];

    if (auth()->user()->isManager()) {
        $menu = [
            'Branch' => [
                ['admin.dashboard', 'speedometer2', 'Dashboard', 'admin.dashboard'],
                ['admin.attendance.index', 'calendar-check', 'Daily Attendance', 'admin.attendance.*'],
                ['admin.leaves.index', 'calendar-x', 'Leave Requests', 'admin.leaves.*'],
                ['admin.corrections.index', 'pencil-square', 'Corrections', 'admin.corrections.*'],
                ['admin.overtime.index', 'hourglass-top', 'Overtime Approvals', 'admin.overtime.*'],
            ],
        ];
    }

    if (auth()->user()->isAdmin()) {
        $menu['Administration'] = [
            ['admin.branches.index', 'building', 'Branches', 'admin.branches.*'],
            ['admin.devices.index', 'hdd-network', 'ZKTeco Devices', 'admin.devices.*'],
            ['admin.users.index', 'person-gear', 'Admin Users', 'admin.users.*'],
            ['admin.audit-logs.index', 'journal-text', 'Audit Log', 'admin.audit-logs.*'],
            ['admin.settings.edit', 'gear', 'Settings', 'admin.settings.*'],
        ];
    }
@endphp
@foreach ($menu as $section => $items)
    <div class="menu-title">{{ $section }}</div>
    <ul class="nav flex-column">
        @foreach ($items as [$route, $icon, $label, $pattern])
            <li class="nav-item">
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
@endforeach
