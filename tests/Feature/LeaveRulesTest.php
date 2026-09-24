<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveType;
use App\Models\PayrollAdjustment;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveRulesTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
        $this->service = app(LeaveService::class);
    }

    public function test_monthly_accrual_counts_months_since_joining(): void
    {
        $type = LeaveType::create(['name' => 'Monthly Casual', 'code' => 'MC', 'days_per_year' => 12, 'accrual' => 'monthly']);
        $old = $this->makeEmployee();
        $new = $this->makeEmployee(['joining_date' => '2026-07-15']);

        $this->assertSame(9.0, $this->service->allocationFor($old, $type, 2026));
        $this->assertSame(3.0, $this->service->allocationFor($new, $type, 2026));
        $this->assertSame(12.0, $this->service->allocationFor($old, $type, 2025));

        $balance = $this->service->balance($new, $type, 2026);
        Carbon::setTestNow('2026-11-01 00:10:00');
        $this->artisan('leave:allocate')->assertSuccessful();
        $this->assertSame(5.0, $balance->fresh()->allocated);
    }

    public function test_gender_restricted_leave(): void
    {
        $maternity = LeaveType::where('code', 'ML')->first();
        $man = $this->makeEmployee(['gender' => 'male']);
        $woman = $this->makeEmployee(['gender' => 'female']);

        $this->assertFalse($this->service->eligibleTypes($man)->contains('id', $maternity->id));
        $this->assertTrue($this->service->eligibleTypes($woman)->contains('id', $maternity->id));

        $this->service->apply($woman, ['leave_type_id' => $maternity->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-05']);

        $this->expectException(ValidationException::class);
        $this->service->apply($man, ['leave_type_id' => $maternity->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-05']);
    }

    public function test_leave_across_new_year_uses_both_balances(): void
    {
        $employee = $this->makeEmployee();
        $casual = LeaveType::where('code', 'CL')->first();

        $leave = $this->service->apply($employee, ['leave_type_id' => $casual->id, 'start_date' => '2026-12-29', 'end_date' => '2027-01-03']);
        $this->service->approve($leave, $this->admin);

        $used = fn ($year) => LeaveBalance::where(['employee_id' => $employee->id, 'leave_type_id' => $casual->id, 'year' => $year])->value('used');
        $this->assertEquals(3, $used(2026));
        $this->assertEquals(2, $used(2027));

        $this->service->cancel($leave->fresh());
        $this->assertEquals(0, $used(2026));
        $this->assertEquals(0, $used(2027));
    }

    public function test_encashment_creates_payroll_earning_and_reduces_balance(): void
    {
        $employee = $this->makeEmployee([], 60000);
        $earned = LeaveType::where('code', 'EL')->first();

        $this->actingAs($this->admin)->post(route('admin.leave-encashments.store'), [
            'employee_id' => $employee->id, 'leave_type_id' => $earned->id, 'year' => 2026, 'days' => 6, 'month' => '2026-12',
        ])->assertRedirect(route('admin.leave-encashments.index'));

        $adjustment = PayrollAdjustment::first();
        $this->assertEqualsWithDelta(30000 / 30 * 6, $adjustment->amount, 0.01);
        $this->assertSame('2026-12-01', $adjustment->month->toDateString());
        $this->assertSame(12.0, $this->service->balance($employee, $earned, 2026)->remaining());

        $this->actingAs($this->admin)->post(route('admin.leave-encashments.store'), [
            'employee_id' => $employee->id, 'leave_type_id' => $earned->id, 'year' => 2026, 'days' => 20, 'month' => '2026-12',
        ])->assertSessionHasErrors('days');

        $this->actingAs($this->admin)->get(route('admin.leave-encashments.index'))->assertOk()->assertSee($employee->name);
        $this->actingAs($this->admin)->delete(route('admin.leave-encashments.destroy', LeaveEncashment::first()));
        $this->assertSame(18.0, $this->service->balance($employee, $earned, 2026)->remaining());
        $this->assertSame(0, PayrollAdjustment::count());
    }

    public function test_non_encashable_type_is_refused(): void
    {
        $employee = $this->makeEmployee([], 30000);

        $this->expectException(ValidationException::class);
        $this->service->encash($employee, LeaveType::where('code', 'CL')->first(), 2026, 1, '2026-12', $this->admin);
    }
}
