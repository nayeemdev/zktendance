<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\LeaveRequest;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        $branchId = $request->user()->managedBranchId() ?? ($request->integer('branch_id') ?: null);
        $inBranch = fn ($q) => $q->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)));

        return view('admin.dashboard', [
            'stats' => $dashboard->adminStats($branchId),
            'branches' => Branch::when($request->user()->managedBranchId(), fn ($q, $id) => $q->whereKey($id))->pluck('name', 'id'),
            'recentPunches' => AttendanceLog::with(['employee', 'device'])->tap($inBranch)->latest('punched_at')->limit(10)->get(),
            'onLeaveToday' => LeaveRequest::with(['employee', 'leaveType'])->tap($inBranch)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->get(),
        ]);
    }
}
