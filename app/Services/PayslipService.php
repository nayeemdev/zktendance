<?php

namespace App\Services;

use App\Mail\PayslipMail;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\SalaryComponent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PayslipService
{
    public function __construct(private PayrollService $payroll) {}

    public function addItem(Payslip $payslip, array $data): PayslipItem
    {
        $this->ensureDraft($payslip);
        $payslip->update(['edited_at' => now()]);
        $item = $payslip->items()->create($data);
        $this->recalculate($payslip);

        return $item;
    }

    public function updateItem(PayslipItem $item, array $data): void
    {
        $this->ensureDraft($item->payslip);
        $item->payslip->update(['edited_at' => now()]);
        $item->update($data);
        $this->recalculate($item->payslip);
    }

    public function removeItem(PayslipItem $item): void
    {
        $this->ensureDraft($item->payslip);
        $item->payslip->update(['edited_at' => now()]);
        $item->delete();
        $this->recalculate($item->payslip);
    }

    public function recalculate(Payslip $payslip): void
    {
        $items = $payslip->items()->get();
        $earnings = round($items->where('type', SalaryComponent::EARNING)->sum('amount'), 2);
        $deductions = round($items->where('type', SalaryComponent::DEDUCTION)->sum('amount'), 2);

        $payslip->update([
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'net_salary' => max(0, round($earnings - $deductions, 2)),
        ]);

        $this->payroll->refreshTotals($payslip->payrollRun);
    }

    private function ensureDraft(Payslip $payslip): void
    {
        if (! $payslip->payrollRun->isDraft()) {
            throw ValidationException::withMessages(['amount' => 'Approved payroll cannot be changed.']);
        }
    }

    public function pdf(Payslip $payslip): \Barryvdh\DomPDF\PDF
    {
        $payslip->loadMissing(['items', 'payrollRun', 'employee.department', 'employee.designation', 'employee.branch']);

        return Pdf::loadView('payslips.pdf', ['payslip' => $payslip])->setPaper('a4');
    }

    public function fileName(Payslip $payslip): string
    {
        return 'payslip-'.$payslip->employee->employee_code.'-'.$payslip->payrollRun->month->format('Y-m').'.pdf';
    }

    public function email(Payslip $payslip): bool
    {
        $email = $payslip->employee->email ?: $payslip->employee->user?->email;
        if (! $email) {
            return false;
        }

        Mail::to($email)->queue(new PayslipMail($payslip));
        $payslip->update(['emailed_at' => now()]);

        return true;
    }

    public function emailRun(PayrollRun $run): int
    {
        return $run->payslips()->with('employee.user')->get()->filter(fn ($p) => $this->email($p))->count();
    }
}
