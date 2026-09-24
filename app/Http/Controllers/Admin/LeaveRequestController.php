<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplyRequest;
use App\Http\Requests\LeaveReviewRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveService $service) {}

    public function index(Request $request)
    {
        $branchId = $request->user()->managedBranchId();
        $leaves = LeaveRequest::with(['employee', 'leaveType', 'reviewer', 'recommender'])
            ->when($request->input('status', 'pending'), fn ($q, $status) => $status === 'all' ? $q : $q->where('status', $status))
            ->when($branchId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $id)))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.leaves.index', ['leaves' => $leaves, 'service' => $this->service]);
    }

    public function create()
    {
        return view('admin.leaves.create', [
            'employees' => Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
            'leaveTypes' => LeaveType::where('is_active', true)->pluck('name', 'id'),
        ]);
    }

    public function store(LeaveApplyRequest $request)
    {
        $leave = $this->service->apply(Employee::findOrFail($request->employee_id), $request->validated());

        if ($request->boolean('approve_now')) {
            $this->service->approve($leave, $request->user());
        }

        return redirect()->route('admin.leaves.index', ['status' => $leave->fresh()->status])->with('success', 'Leave saved.');
    }

    public function recommend(LeaveReviewRequest $request, LeaveRequest $leave)
    {
        abort_unless($this->service->canRecommend($request->user(), $leave), 403);
        $this->service->recommend($leave, $request->user(), $request->review_note);

        return back()->with('success', 'Leave recommended. HR will give the final approval.');
    }

    public function approve(LeaveReviewRequest $request, LeaveRequest $leave)
    {
        abort_unless($this->service->canApprove($request->user(), $leave), 403);
        $this->service->approve($leave, $request->user(), $request->review_note);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(LeaveReviewRequest $request, LeaveRequest $leave)
    {
        abort_unless($this->service->canReject($request->user(), $leave), 403);
        $this->service->reject($leave, $request->user(), $request->review_note);

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->isOpen() || $leave->status === 'approved', 422);
        $this->service->cancel($leave, $request->user());

        return back()->with('success', 'Leave cancelled.');
    }
}
