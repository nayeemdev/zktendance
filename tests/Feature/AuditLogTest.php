<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
    }

    public function test_salary_change_is_logged_with_user_and_values(): void
    {
        $employee = $this->makeEmployee([], 30000);
        $salary = $employee->salaries()->first();

        $this->actingAs($this->admin);
        $salary->update(['gross_salary' => 35000]);

        $log = AuditLog::where('event', 'updated')->where('auditable_type', get_class($salary))->latest('id')->first();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertEquals(30000, $log->old_values['gross_salary']);
        $this->assertEquals(35000, $log->new_values['gross_salary']);
        $this->assertSame('Salary of '.$employee->name, $log->description);
    }

    public function test_automatic_attendance_is_not_logged_but_manual_is(): void
    {
        $employee = $this->makeEmployee();
        $service = app(AttendanceService::class);

        $service->processDate(Carbon::parse('2026-09-14'));
        $this->assertSame(0, AuditLog::where('auditable_type', Attendance::class)->count());

        $this->actingAs($this->admin);
        $service->saveManual($employee, Carbon::parse('2026-09-14'), '09:00', '18:00', null, 'Forgot card');
        $this->assertSame(1, AuditLog::where('auditable_type', Attendance::class)->count());
    }

    public function test_password_is_never_stored(): void
    {
        $this->actingAs($this->admin);
        $this->admin->update(['password' => 'newpassword']);

        $this->assertFalse(AuditLog::where('auditable_type', get_class($this->admin))->get()->contains(
            fn ($log) => isset($log->new_values['password']) || isset($log->old_values['password'])
        ));
    }

    public function test_login_is_logged_and_page_filters(): void
    {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->assertTrue(AuditLog::where('event', 'login')->exists());

        $this->makeEmployee(['name' => 'Audited Person']);

        $this->actingAs($this->admin)->get(route('admin.audit-logs.index', ['type' => 'Employee']))
            ->assertOk()
            ->assertSee('Audited Person');
        $this->actingAs($this->admin)->get(route('admin.audit-logs.index', ['event' => 'updated']))->assertOk();
    }

    public function test_hr_cannot_view_audit_log(): void
    {
        $hr = User::create(['name' => 'HR', 'email' => 'hr@example.com', 'password' => 'password', 'role' => 'hr']);

        $this->actingAs($hr)->get(route('admin.audit-logs.index'))->assertForbidden();
    }
}
