<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollRunRequest;
use App\Models\Branch;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use App\Services\PayslipService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function __construct(private PayrollService $service) {}

    public function index()
    {
        $runs = PayrollRun::with('branch')->withCount('payslips')->latest('month')->paginate(20);

        return view('admin.payroll.index', compact('runs'));
    }

    public function create()
    {
        return view('admin.payroll.create', ['branches' => Branch::pluck('name', 'id')]);
    }

    public function store(PayrollRunRequest $request)
    {
        $run = $this->service->create($request->month, $request->branch_id, $request->user());

        return redirect()->route('admin.payroll.show', $run)->with('success', 'Payroll generated as draft. Review it, then approve.');
    }

    public function show(PayrollRun $payroll, Request $request)
    {
        $payslips = $payroll->payslips()
            ->with(['employee.department', 'employee.designation'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where('name', 'like', "%{$s}%")->orWhere('employee_code', 'like', "%{$s}%")))
            ->get()
            ->sortBy('employee.employee_code');

        return view('admin.payroll.show', ['run' => $payroll->load(['branch', 'approver']), 'payslips' => $payslips]);
    }

    public function regenerate(PayrollRun $payroll)
    {
        $this->service->generate($payroll);

        return back()->with('success', 'Payroll regenerated with the latest attendance and salary data.');
    }

    public function approve(Request $request, PayrollRun $payroll, PayslipService $payslips)
    {
        $this->service->approve($payroll, $request->user());

        if (setting('email_payslips')) {
            $payslips->emailRun($payroll);
        }

        return back()->with('success', 'Payroll approved. Payslips are now visible to employees.');
    }

    public function pay(PayrollRun $payroll)
    {
        $this->service->markPaid($payroll);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function email(PayrollRun $payroll, PayslipService $payslips)
    {
        abort_if($payroll->isDraft(), 422, 'Approve the payroll first.');
        $count = $payslips->emailRun($payroll);

        return back()->with('success', "{$count} payslips queued for email.");
    }

    public function export(PayrollRun $payroll, ReportService $reports)
    {
        $payslips = $payroll->payslips()->with(['employee.department', 'items'])->get()->sortBy('employee.employee_code');

        $rows = $payslips->map(fn ($p) => [
            $p->employee->employee_code,
            $p->employee->name,
            $p->employee->department?->name,
            $p->present_days,
            $p->absent_days,
            $p->paid_leave_days,
            $p->unpaid_leave_days,
            $p->late_days,
            $p->overtime_hours,
            $p->total_earnings,
            $p->total_deductions,
            $p->net_salary,
            $p->employee->bank_name,
            $p->employee->bank_account_no,
        ]);

        return $reports->csv(
            'payroll-'.$payroll->month->format('Y-m').'.csv',
            ['Code', 'Name', 'Department', 'Present', 'Absent', 'Paid Leave', 'Unpaid Leave', 'Late', 'OT Hours', 'Earnings', 'Deductions', 'Net Pay', 'Bank', 'Account No'],
            $rows
        );
    }

    public function destroy(PayrollRun $payroll)
    {
        abort_unless($payroll->isDraft(), 422, 'Only draft payroll can be deleted.');
        $payroll->delete();

        return redirect()->route('admin.payroll.index')->with('success', 'Draft payroll deleted.');
    }
}
