<?php

namespace App\Services;

use App\Mail\PayslipMail;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class PayslipService
{
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
