@php
    $menu = [
        'Main' => [
            ['admin.dashboard', 'dashboard-square-02', 'Dashboard', 'admin.dashboard'],
            ['admin.employees.index', 'user-group', 'Employees', 'admin.employees.*'],
            ['admin.documents.index', 'folder-open', 'Expiring Documents', 'admin.documents.*'],
        ],
        'Attendance' => [
            ['admin.attendance.index', 'calendar-check-in-01', 'Daily Attendance', 'admin.attendance.*'],
            ['admin.punches.index', 'fingerprint-scan', 'Device Punches', 'admin.punches.*'],
            ['admin.corrections.index', 'task-edit-01', 'Corrections', 'admin.corrections.*'],
            ['admin.overtime.index', 'time-quarter-pass', 'Overtime Approvals', 'admin.overtime.*'],
            ['admin.shifts.index', 'clock-01', 'Shifts', 'admin.shifts.*'],
            ['admin.roster.index', 'calendar-02', 'Shift Roster', 'admin.roster.*'],
            ['admin.holidays.index', 'calendar-03', 'Holidays', 'admin.holidays.*'],
        ],
        'Leave' => [
            ['admin.leaves.index', 'calendar-remove-01', 'Leave Requests', 'admin.leaves.*'],
            ['admin.leave-balances.index', 'chart-histogram', 'Leave Balances', 'admin.leave-balances.*'],
            ['admin.leave-types.index', 'tag-01', 'Leave Types', 'admin.leave-types.*'],
            ['admin.leave-encashments.index', 'coins-01', 'Leave Encashment', 'admin.leave-encashments.*'],
        ],
        'Payroll' => [
            ['admin.payroll.index', 'money-bag-02', 'Payroll Runs', 'admin.payroll.*'],
            ['admin.salary-structures.index', 'structure-03', 'Salary Structures', 'admin.salary-structures.*'],
            ['admin.salary-components.index', 'left-to-right-list-bullet', 'Salary Components', 'admin.salary-components.*'],
            ['admin.overtime-rules.index', 'hourglass', 'Overtime Rules', 'admin.overtime-rules.*'],
            ['admin.adjustments.index', 'plus-minus', 'Bonus & Deductions', 'admin.adjustments.*'],
            ['admin.loans.index', 'bank', 'Loans & Advances', 'admin.loans.*'],
        ],
        'Company' => [
            ['admin.reports.index', 'analytics-01', 'Reports', 'admin.reports.*'],
            ['admin.notices.index', 'megaphone-01', 'Notices', 'admin.notices.*'],
            ['admin.departments.index', 'hierarchy-square-02', 'Departments', 'admin.departments.*'],
            ['admin.designations.index', 'id', 'Designations', 'admin.designations.*'],
        ],
    ];

    if (auth()->user()->isManager()) {
        $menu = [
            'Branch' => [
                ['admin.dashboard', 'dashboard-square-02', 'Dashboard', 'admin.dashboard'],
                ['admin.attendance.index', 'calendar-check-in-01', 'Daily Attendance', 'admin.attendance.*'],
                ['admin.leaves.index', 'calendar-remove-01', 'Leave Requests', 'admin.leaves.*'],
                ['admin.corrections.index', 'task-edit-01', 'Corrections', 'admin.corrections.*'],
                ['admin.overtime.index', 'time-quarter-pass', 'Overtime Approvals', 'admin.overtime.*'],
            ],
        ];
    }

    if (auth()->user()->isAdmin()) {
        $menu['Administration'] = [
            ['admin.branches.index', 'building-03', 'Branches', 'admin.branches.*'],
            ['admin.devices.index', 'router-01', 'ZKTeco Devices', 'admin.devices.*'],
            ['admin.users.index', 'user-settings-01', 'Admin Users', 'admin.users.*'],
            ['admin.audit-logs.index', 'note-01', 'Audit Log', 'admin.audit-logs.*'],
            ['admin.settings.edit', 'settings-02', 'Settings', 'admin.settings.*'],
        ];
    }
@endphp
@foreach ($menu as $section => $items)
    <div class="menu-title">{{ $section }}</div>
    <ul class="nav flex-column">
        @foreach ($items as [$route, $icon, $label, $pattern])
            <li class="nav-item">
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <i class="hgi-stroke hgi-{{ $icon }} me-2"></i>{{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
@endforeach
