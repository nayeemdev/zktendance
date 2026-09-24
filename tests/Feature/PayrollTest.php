<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Services\AttendanceLogService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
    }

    public function test_bangladesh_tax_calculation(): void
    {
        $tax = app(TaxService::class);

        $this->assertSame(0.0, $tax->annualTax(500000));
        $this->assertEqualsWithDelta(5000, $tax->annualTax(600000), 0.01);
        $this->assertEqualsWithDelta(135000, $tax->annualTax(1800000), 0.5);
    }

    public function test_monthly_payroll_from_attendance(): void
    {
        $employee = $this->makeEmployee([], 50000);
        $absent = ['2026-08-03', '2026-08-04'];
        $late = ['2026-08-05', '2026-08-06', '2026-08-08'];
        $records = [];

        for ($date = Carbon::parse('2026-08-01'); $date->month === 8; $date->addDay()) {
            if ($date->isFriday() || in_array($date->toDateString(), $absent)) {
                continue;
            }

            $in = in_array($date->toDateString(), $late) ? '09:30:00' : '08:55:00';
            $out = $date->toDateString() === '2026-08-10' ? '20:00:00' : '18:00:00';
            $records[] = ['user_id' => $employee->device_user_id, 'timestamp' => $date->toDateString().' '.$in];
            $records[] = ['user_id' => $employee->device_user_id, 'timestamp' => $date->toDateString().' '.$out];
        }
        app(AttendanceLogService::class)->store(null, $records);

        Loan::create(['employee_id' => $employee->id, 'amount' => 10000, 'installment' => 2000, 'start_month' => '2026-08-01']);
        PayrollAdjustment::create(['employee_id' => $employee->id, 'month' => '2026-08-01', 'type' => 'earning', 'title' => 'Performance Bonus', 'amount' => 5000]);

        $this->actingAs($this->admin)->post(route('admin.payroll.store'), ['month' => '2026-08'])->assertRedirect();

        $run = PayrollRun::first();
        $payslip = $run->payslips()->with('items')->first();
        $items = $payslip->items->pluck('amount', 'name');

        $perDay = 50000 / 31;
        $this->assertSame(2.0, $payslip->absent_days);
        $this->assertSame(3, $payslip->late_days);
        $this->assertSame(4, $payslip->off_days);
        $this->assertSame(25.0, $payslip->present_days);
        $this->assertSame(2.0, $payslip->overtime_hours);

        $this->assertEqualsWithDelta(25000, $items['Basic Salary'], 0.01);
        $this->assertEqualsWithDelta(2 * $perDay, $items['Absent / Unpaid Leave (2 days)'], 0.01);
        $this->assertEqualsWithDelta($perDay, $items['Late Deduction (3 lates = 1 days)'], 0.01);
        $this->assertEqualsWithDelta(25000 / 208 * 2 * 2, $items['Overtime (2 hrs)'], 0.01);
        $this->assertEqualsWithDelta(5000, $items['Performance Bonus'], 0.01);
        $this->assertEqualsWithDelta(2000, $items['Loan Installment'], 0.01);
        $this->assertEqualsWithDelta(5000 / 12, $items['Income Tax (TDS)'], 0.01);

        $earnings = 50000 + 25000 / 208 * 4 + 5000;
        $deductions = 3 * $perDay + 2000 + 5000 / 12;
        $this->assertEqualsWithDelta($earnings, $payslip->total_earnings, 0.05);
        $this->assertEqualsWithDelta($earnings - $deductions, $payslip->net_salary, 0.05);
        $this->assertEqualsWithDelta($payslip->net_salary, $run->fresh()->total_net, 0.01);

        $this->actingAs($this->admin)->post(route('admin.payroll.approve', $run))->assertSessionHas('success');
        $this->assertSame(PayrollRun::APPROVED, $run->fresh()->status);
        $this->assertSame(2000.0, Loan::first()->paid_amount);

        $this->actingAs($this->admin)->get(route('admin.payslips.pdf', $payslip))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('admin.payroll.export', $run))->assertOk();
    }

    public function test_new_joiner_salary_is_prorated(): void
    {
        $employee = $this->makeEmployee(['joining_date' => '2026-08-17', 'tax_enabled' => false], 31000);

        $this->actingAs($this->admin)->post(route('admin.payroll.store'), ['month' => '2026-08'])->assertSessionHasNoErrors();

        $payslip = PayrollRun::first()->payslips()->where('employee_id', $employee->id)->first();
        $this->assertEqualsWithDelta(15000, $payslip->total_earnings, 0.05);
    }

    public function test_duplicate_run_for_same_month_is_blocked(): void
    {
        $this->makeEmployee([], 30000);
        $this->actingAs($this->admin)->post(route('admin.payroll.store'), ['month' => '2026-08']);
        $this->actingAs($this->admin)->post(route('admin.payroll.store'), ['month' => '2026-08'])->assertSessionHasErrors('month');

        $this->assertSame(1, PayrollRun::count());
    }
}
