<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $payslips = $request->user()->employee->payslips()
            ->with('payrollRun')
            ->whereHas('payrollRun', fn ($q) => $q->where('status', '!=', PayrollRun::DRAFT))
            ->latest()
            ->paginate(24);

        return view('portal.payslips', compact('payslips'));
    }

    public function show(Request $request, Payslip $payslip)
    {
        $this->authorizeOwner($request, $payslip);

        return view('payslips.show', ['payslip' => $payslip->load(['items', 'payrollRun', 'employee.department', 'employee.designation', 'employee.branch'])]);
    }

    public function pdf(Request $request, Payslip $payslip, PayslipService $service)
    {
        $this->authorizeOwner($request, $payslip);

        return $service->pdf($payslip)->download($service->fileName($payslip));
    }

    private function authorizeOwner(Request $request, Payslip $payslip): void
    {
        abort_unless($payslip->employee_id === $request->user()->employee->id && ! $payslip->payrollRun->isDraft(), 403);
    }
}
