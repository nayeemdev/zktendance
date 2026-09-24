<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\PayslipService;

class PayslipController extends Controller
{
    public function __construct(private PayslipService $service) {}

    public function show(Payslip $payslip)
    {
        return view('payslips.show', ['payslip' => $payslip->load(['items', 'payrollRun', 'employee.department', 'employee.designation', 'employee.branch'])]);
    }

    public function pdf(Payslip $payslip)
    {
        return $this->service->pdf($payslip)->download($this->service->fileName($payslip));
    }

    public function email(Payslip $payslip)
    {
        abort_if($payslip->payrollRun->isDraft(), 422, 'Approve the payroll first.');

        return $this->service->email($payslip)
            ? back()->with('success', 'Payslip queued for email.')
            : back()->with('error', 'This employee has no email address.');
    }
}
