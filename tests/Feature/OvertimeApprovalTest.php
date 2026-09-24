<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\OvertimeRule;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AttendanceLogService;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OvertimeApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
        OvertimeRule::first()->update(['requires_approval' => true]);
    }

    private function workLate($employee, string $date): Attendance
    {
        app(AttendanceLogService::class)->store(null, [
            ['user_id' => $employee->device_user_id, 'timestamp' => "$date 09:00:00"],
            ['user_id' => $employee->device_user_id, 'timestamp' => "$date 20:00:00"],
        ]);

        return Attendance::where('employee_id', $employee->id)->whereDate('date', $date)->first();
    }

    public function test_overtime_waits_for_approval_and_decision_survives_reprocess(): void
    {
        $employee = $this->makeEmployee();
        $day = $this->workLate($employee, '2026-09-14');
        $this->assertSame('pending', $day->overtime_status);
        $this->assertSame(120, $day->overtime_minutes);

        $this->actingAs($this->admin)->get(route('admin.overtime.index', ['month' => '2026-09']))->assertOk()->assertSee($employee->name);
        $this->actingAs($this->admin)->post(route('admin.overtime.review'), ['ids' => [$day->id], 'decision' => 'approved'])->assertSessionHas('success');
        $this->assertSame('approved', $day->fresh()->overtime_status);

        app(AttendanceService::class)->processDate(Carbon::parse('2026-09-14'));
        $this->assertSame('approved', $day->fresh()->overtime_status);
    }

    public function test_payroll_pays_only_approved_overtime(): void
    {
        $employee = $this->makeEmployee([], 52000);
        $approved = $this->workLate($employee, '2026-08-10');
        $this->workLate($employee, '2026-08-11');
        $rejected = $this->workLate($employee, '2026-08-12');
        $approved->update(['overtime_status' => 'approved']);
        $rejected->update(['overtime_status' => 'rejected']);

        $this->actingAs($this->admin)->post(route('admin.payroll.store'), ['month' => '2026-08']);

        $this->assertSame(2.0, PayrollRun::first()->payslips()->first()->overtime_hours);
    }

    public function test_manager_reviews_only_own_branch(): void
    {
        $other = Branch::create(['name' => 'Other', 'code' => 'OT', 'weekend_days' => [5]]);
        $manager = User::create(['name' => 'M', 'email' => 'm@example.com', 'password' => 'password', 'role' => 'manager', 'branch_id' => $other->id]);
        $day = $this->workLate($this->makeEmployee(), '2026-09-14');

        $this->actingAs($manager)->get(route('admin.overtime.index', ['month' => '2026-09']))->assertOk();
        $this->actingAs($manager)->post(route('admin.overtime.review'), ['ids' => [$day->id], 'decision' => 'approved']);
        $this->assertSame('pending', $day->fresh()->overtime_status);
    }

    public function test_no_approval_needed_when_rule_says_so(): void
    {
        OvertimeRule::first()->update(['requires_approval' => false]);
        $day = $this->workLate($this->makeEmployee(), '2026-09-14');

        $this->assertNull($day->overtime_status);
    }
}
