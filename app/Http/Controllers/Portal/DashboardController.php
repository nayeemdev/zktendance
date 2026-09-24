<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\LeaveType;
use App\Models\Notice;
use App\Services\LeaveService;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PayrollService $payroll, LeaveService $leaves)
    {
        $employee = $request->user()->employee->load(['branch', 'department', 'designation', 'shift']);

        return view('portal.dashboard', [
            'employee' => $employee,
            'today' => $employee->attendances()->whereDate('date', today())->first(),
            'punches' => AttendanceLog::where('employee_id', $employee->id)->whereDate('punched_at', today())->orderBy('punched_at')->get(),
            'summary' => $payroll->attendanceSummary($employee, today()->startOfMonth(), today()),
            'balances' => LeaveType::where('is_active', true)->get()->map(fn ($type) => $leaves->balance($employee, $type, now()->year)->setRelation('leaveType', $type)),
            'notices' => Notice::where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
                ->whereDate('published_on', '<=', today())
                ->latest('published_on')
                ->limit(5)
                ->get(),
        ]);
    }
}
