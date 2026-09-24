<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplyRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $service) {}

    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        return view('portal.leaves.index', [
            'leaves' => $employee->leaveRequests()->with('leaveType')->latest()->paginate(20),
            'balances' => LeaveType::where('is_active', true)->get()->map(fn ($type) => $this->service->balance($employee, $type, now()->year)->setRelation('leaveType', $type)),
        ]);
    }

    public function create()
    {
        return view('portal.leaves.create', ['leaveTypes' => LeaveType::where('is_active', true)->get()]);
    }

    public function store(LeaveApplyRequest $request)
    {
        $this->service->apply($request->user()->employee, $request->validated());

        return redirect()->route('portal.leaves.index')->with('success', 'Leave request submitted.');
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->employee_id === $request->user()->employee->id && $leave->status === 'pending', 403);

        $this->service->cancel($leave, $request->user());

        return back()->with('success', 'Leave request cancelled.');
    }
}
