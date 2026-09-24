<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PayslipItem;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayslipEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
        $this->makeEmployee(['tax_enabled' => false], 40000);
        $this->makeEmployee(['tax_enabled' => false], 20000);
    }

    public function test_line_changes_update_totals_and_are_audited(): void
    {
        $run = app(PayrollService::class)->create('2026-08', null, $this->admin);
        $payslip = $run->payslips()->with('items')->first();
        $basic = $payslip->items->firstWhere('name', 'Basic Salary');
        $before = $payslip->net_salary;

        $this->actingAs($this->admin)->put(route('admin.payslip-items.update', $basic), ['name' => 'Basic Salary', 'amount' => $basic->amount + 1000])->assertSessionHas('success');
        $this->assertEqualsWithDelta($before + 1000, $payslip->fresh()->net_salary, 0.01);
        $this->assertNotNull($payslip->fresh()->edited_at);

        $this->actingAs($this->admin)->post(route('admin.payslips.items.store', $payslip), ['type' => 'deduction', 'name' => 'Canteen', 'amount' => 500]);
        $this->assertEqualsWithDelta($before + 500, $payslip->fresh()->net_salary, 0.01);

        $this->actingAs($this->admin)->delete(route('admin.payslip-items.destroy', PayslipItem::where('name', 'Canteen')->first()));
        $this->assertEqualsWithDelta($before + 1000, $payslip->fresh()->net_salary, 0.01);
        $this->assertEqualsWithDelta($run->payslips()->sum('net_salary'), $run->fresh()->total_net, 0.01);

        $this->assertTrue(AuditLog::where('auditable_type', PayslipItem::class)->where('event', 'updated')->exists());
        $this->actingAs($this->admin)->get(route('admin.payslips.show', $payslip))->assertOk()->assertSee('Edit Lines');
    }

    public function test_approved_payroll_is_locked(): void
    {
        $run = app(PayrollService::class)->create('2026-08', null, $this->admin);
        app(PayrollService::class)->approve($run, $this->admin);
        $item = $run->payslips()->first()->items()->first();

        $this->actingAs($this->admin)->put(route('admin.payslip-items.update', $item), ['name' => 'X', 'amount' => 1])->assertSessionHasErrors('amount');
        $this->assertNotSame('X', $item->fresh()->name);
    }
}
