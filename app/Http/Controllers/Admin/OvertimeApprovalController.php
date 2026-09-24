<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimeReviewRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Services\OvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OvertimeApprovalController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $branchId = $request->user()->managedBranchId() ?? $request->integer('branch_id');

        $rows = Attendance::with(['employee.branch', 'overtimeReviewer'])
            ->where('overtime_status', $request->input('status', 'pending'))
            ->whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when($branchId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $id)))
            ->orderBy('date')
            ->get();

        return view('admin.overtime-approvals', [
            'rows' => $rows,
            'month' => $month,
            'branches' => Branch::when($request->user()->managedBranchId(), fn ($q, $id) => $q->whereKey($id))->pluck('name', 'id'),
        ]);
    }

    public function review(OvertimeReviewRequest $request, OvertimeService $service)
    {
        $count = $service->review($request->validated('ids'), $request->validated('decision'), $request->user());

        return back()->with('success', "{$count} overtime entries {$request->validated('decision')}.");
    }
}
