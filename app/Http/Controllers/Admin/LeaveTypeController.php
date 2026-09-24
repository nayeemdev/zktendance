<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function index()
    {
        return view('admin.leave-types.index', ['leaveTypes' => LeaveType::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.leave-types.form', ['leaveType' => new LeaveType(['is_paid' => true, 'allow_half_day' => true, 'is_active' => true, 'accrual' => 'yearly'])]);
    }

    public function store(LeaveTypeRequest $request)
    {
        LeaveType::create($request->validated());

        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('admin.leave-types.form', compact('leaveType'));
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType)
    {
        $leaveType->update($request->validated());

        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->update(['is_active' => false]);

        return back()->with('success', 'Leave type disabled.');
    }
}
