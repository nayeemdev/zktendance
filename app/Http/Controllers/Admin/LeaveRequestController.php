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
        $leaves = LeaveRequest::with(['employee', 'leaveType', 'reviewer'])
            ->when($request->input('status', 'pending'), fn ($q, $status) => $status === 'all' ? $q : $q->where('status', $status))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.leaves.index', compact('leaves'));
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

    public function approve(LeaveReviewRequest $request, LeaveRequest $leave)
    {
        abort_unless($leave->status === 'pending', 422);
        $this->service->approve($leave, $request->user(), $request->review_note);

        return back()->with('success', 'Leave approved.');
    }

    public function reject(LeaveReviewRequest $request, LeaveRequest $leave)
    {
        abort_unless($leave->status === 'pending', 422);
        $this->service->reject($leave, $request->user(), $request->review_note);

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        abort_unless(in_array($leave->status, ['pending', 'approved']), 422);
        $this->service->cancel($leave, $request->user());

        return back()->with('success', 'Leave cancelled.');
    }
}
