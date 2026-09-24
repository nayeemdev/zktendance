<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request, PayrollService $payroll)
    {
        $employee = $request->user()->employee;
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');

        $attendances = $employee->attendances()
            ->whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->get();

        return view('portal.attendance', [
            'month' => $month,
            'attendances' => $attendances,
            'summary' => $payroll->attendanceSummary($employee, $month->copy()->startOfMonth(), $month->copy()->endOfMonth()->min(today())),
        ]);
    }
}
