<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
    }

    public function test_employee_applies_and_admin_approves(): void
    {
        $employee = $this->makeEmployee();
        $user = User::create(['name' => $employee->name, 'email' => 'e@example.com', 'password' => 'password', 'role' => 'employee']);
        $employee->update(['user_id' => $user->id]);
        $casual = LeaveType::where('code', 'CL')->first();

        $this->actingAs($user)->post(route('portal.leaves.store'), [
            'leave_type_id' => $casual->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-14',
            'reason' => 'Family program',
        ])->assertRedirect(route('portal.leaves.index'));

        $leave = LeaveRequest::first();
        $this->assertSame(4.0, $leave->days);
        $this->assertSame('pending', $leave->status);

        $this->actingAs($this->admin)->post(route('admin.leaves.approve', $leave))->assertSessionHas('success');

        $balance = LeaveBalance::where(['employee_id' => $employee->id, 'leave_type_id' => $casual->id, 'year' => 2026])->first();
        $this->assertSame(4.0, $balance->used);
        $this->assertSame(6.0, $balance->remaining());

        $status = fn ($date) => Attendance::where('employee_id', $employee->id)->whereDate('date', $date)->value('status');
        $this->assertSame(Attendance::LEAVE, $status('2026-09-10'));
        $this->assertSame(Attendance::WEEKEND, $status('2026-09-11'));
        $this->assertSame(Attendance::LEAVE, $status('2026-09-14'));

        $this->actingAs($this->admin)->post(route('admin.leaves.cancel', $leave));
        $this->assertSame(0.0, $balance->fresh()->used);
        $this->assertSame(Attendance::ABSENT, $status('2026-09-10'));
    }

    public function test_cannot_take_more_than_balance(): void
    {
        $employee = $this->makeEmployee();
        $casual = LeaveType::where('code', 'CL')->first();

        $this->expectException(ValidationException::class);
        app(LeaveService::class)->apply($employee, ['leave_type_id' => $casual->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-20']);
    }

    public function test_overlapping_request_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $casual = LeaveType::where('code', 'CL')->first();
        $service = app(LeaveService::class);
        $service->apply($employee, ['leave_type_id' => $casual->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-06']);

        $this->expectException(ValidationException::class);
        $service->apply($employee, ['leave_type_id' => $casual->id, 'start_date' => '2026-10-06', 'end_date' => '2026-10-07']);
    }

    public function test_half_day_counts_as_half(): void
    {
        $employee = $this->makeEmployee();
        $sick = LeaveType::where('code', 'SL')->first();

        $leave = app(LeaveService::class)->apply($employee, ['leave_type_id' => $sick->id, 'start_date' => '2026-10-05', 'is_half_day' => true]);
        $this->assertSame(0.5, $leave->days);
    }

    public function test_carry_forward_uses_limit(): void
    {
        $employee = $this->makeEmployee();
        $earned = LeaveType::where('code', 'EL')->first();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $earned->id, 'year' => 2025, 'allocated' => 18, 'carried_forward' => 30, 'used' => 2]);

        $this->artisan('leave:allocate', ['year' => 2026])->assertSuccessful();

        $balance = LeaveBalance::where(['employee_id' => $employee->id, 'leave_type_id' => $earned->id, 'year' => 2026])->first();
        $this->assertSame(40.0, $balance->carried_forward);
        $this->assertSame(18.0, $balance->allocated);
    }
}
