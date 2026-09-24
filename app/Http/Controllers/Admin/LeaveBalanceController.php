<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocateLeaveRequest;
use App\Http\Requests\LeaveBalanceRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $employees = Employee::active()
            ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->orderBy('employee_code')
            ->paginate(30)
            ->withQueryString();

        $balances = LeaveBalance::where('year', $year)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy('leave_type_id'));

        return view('admin.leaves.balances', [
            'year' => $year,
            'employees' => $employees,
            'balances' => $balances,
            'leaveTypes' => LeaveType::where('is_active', true)->get(),
            'branches' => Branch::pluck('name', 'id'),
        ]);
    }

    public function allocate(AllocateLeaveRequest $request, LeaveService $service)
    {
        $year = (int) $request->validated('year');
        $count = $service->allocateYear($year);

        return back()->with('success', "{$count} leave balances checked for {$year}.");
    }

    public function update(LeaveBalanceRequest $request, LeaveBalance $balance)
    {
        $balance->update($request->validated());

        return back()->with('success', 'Balance updated.');
    }
}
