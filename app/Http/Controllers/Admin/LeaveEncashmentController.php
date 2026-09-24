<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveEncashmentRequest;
use App\Models\Employee;
use App\Models\LeaveEncashment;
use App\Models\LeaveType;
use App\Services\LeaveService;

class LeaveEncashmentController extends Controller
{
    public function __construct(private LeaveService $service) {}

    public function index()
    {
        return view('admin.leaves.encashments', [
            'encashments' => LeaveEncashment::with(['employee', 'leaveType', 'adjustment', 'creator'])->latest()->paginate(25),
        ]);
    }

    public function create()
    {
        return view('admin.leaves.encash', [
            'employees' => Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
            'leaveTypes' => LeaveType::where('is_encashable', true)->where('is_active', true)->pluck('name', 'id'),
        ]);
    }

    public function store(LeaveEncashmentRequest $request)
    {
        $data = $request->validated();
        $this->service->encash(
            Employee::findOrFail($data['employee_id']),
            LeaveType::findOrFail($data['leave_type_id']),
            (int) $data['year'],
            (float) $data['days'],
            $data['month'],
            $request->user(),
        );

        return redirect()->route('admin.leave-encashments.index')->with('success', 'Encashment added to payroll for '.$data['month'].'.');
    }

    public function destroy(LeaveEncashment $encashment)
    {
        $this->service->removeEncashment($encashment);

        return back()->with('success', 'Encashment removed and days returned to the balance.');
    }
}
