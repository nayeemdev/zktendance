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
        return view('admin.dashboard', [
            'stats' => $dashboard->adminStats($request->integer('branch_id') ?: null),
            'branches' => Branch::pluck('name', 'id'),
            'recentPunches' => AttendanceLog::with(['employee', 'device'])->latest('punched_at')->limit(10)->get(),
            'onLeaveToday' => LeaveRequest::with(['employee', 'leaveType'])
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->get(),
        ]);
    }
}
