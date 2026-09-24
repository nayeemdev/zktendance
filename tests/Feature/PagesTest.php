<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_loads(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::where('email', 'admin@example.com')->first();
        $run = app(PayrollService::class)->create(now()->subMonth()->format('Y-m'), null, $admin);

        $employee = Employee::first();
        $routes = [
            'admin.dashboard', 'admin.employees.index', 'admin.employees.create', 'admin.departments.index', 'admin.departments.create',
            'admin.designations.index', 'admin.shifts.index', 'admin.shifts.create', 'admin.holidays.index', 'admin.holidays.create',
            'admin.attendance.index', 'admin.attendance.create', 'admin.punches.index', 'admin.corrections.index',
            'admin.leave-types.index', 'admin.leave-types.create', 'admin.leaves.index', 'admin.leaves.create', 'admin.leave-balances.index',
            'admin.salary-components.index', 'admin.salary-components.create', 'admin.salary-structures.index', 'admin.salary-structures.create',
            'admin.overtime-rules.index', 'admin.overtime-rules.create', 'admin.loans.index', 'admin.loans.create',
            'admin.adjustments.index', 'admin.adjustments.create', 'admin.payroll.index', 'admin.payroll.create',
            'admin.reports.index', 'admin.reports.monthly-summary', 'admin.reports.monthly-sheet', 'admin.reports.late',
            'admin.notices.index', 'admin.notices.create', 'admin.settings.edit', 'admin.users.index', 'admin.users.create',
            'admin.branches.index', 'admin.branches.create', 'admin.devices.index', 'admin.devices.create', 'profile.edit',
            'admin.roster.index', 'admin.roster.create', 'admin.overtime.index', 'admin.audit-logs.index', 'admin.leave-encashments.index',
            'admin.leave-encashments.create', 'admin.documents.index', 'notifications.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.employees.show', $employee))->assertOk();
        $this->actingAs($admin)->get(route('admin.employees.edit', $employee))->assertOk();
        $this->actingAs($admin)->get(route('admin.devices.show', Device::first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.devices.users', Device::first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.devices.show', Device::where('connection_mode', 'push')->first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.attendance.edit', Attendance::first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.payroll.show', $run))->assertOk()->assertSee($employee->name);
        $this->actingAs($admin)->get(route('admin.payslips.show', $run->payslips()->first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.salary-structures.edit', 1))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.monthly-summary', ['export' => 'csv']))->assertOk();
    }

    public function test_every_portal_page_loads(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::where('email', 'admin@example.com')->first();
        $run = app(PayrollService::class)->create(now()->subMonth()->format('Y-m'), null, $admin);
        app(PayrollService::class)->approve($run, $admin);

        $user = User::where('email', 'employee1@example.com')->first();
        foreach (['portal.dashboard', 'portal.attendance', 'portal.leaves.index', 'portal.leaves.create', 'portal.corrections.index', 'portal.corrections.create', 'portal.payslips.index', 'portal.documents.index', 'profile.edit', 'notifications.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }

        $own = $user->employee->payslips()->first();
        $this->actingAs($user)->get(route('portal.payslips.show', $own))->assertOk();
        $this->actingAs($user)->get(route('portal.payslips.pdf', $own))->assertOk();

        $other = $run->payslips()->where('employee_id', '!=', $user->employee->id)->first();
        $this->actingAs($user)->get(route('portal.payslips.show', $other))->assertForbidden();
    }

    public function test_hr_cannot_open_admin_only_pages(): void
    {
        $this->seed(DemoSeeder::class);
        $hr = User::where('role', 'hr')->first();

        $this->actingAs($hr)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($hr)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($hr)->get(route('admin.devices.index'))->assertForbidden();
    }
}
