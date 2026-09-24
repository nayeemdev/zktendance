<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\ProcessAttendanceRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));

        $attendances = Attendance::with(['employee.branch', 'employee.department', 'shift'])
            ->whereDate('date', $date)
            ->whereHas('employee', fn ($q) => $q
                ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
                ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->get()
            ->sortBy('employee.employee_code');

        return view('admin.attendance.index', [
            'date' => $date,
            'attendances' => $attendances,
            'branches' => Branch::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'summary' => $attendances->countBy('status'),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.attendance.form', [
            'attendance' => new Attendance(['date' => $request->input('date', today()->toDateString())]),
            'employees' => Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
        ]);
    }

    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();
        $attendance = $this->service->saveManual(
            Employee::findOrFail($data['employee_id']),
            Carbon::parse($data['date']),
            $data['check_in'] ?? null,
            $data['check_out'] ?? null,
            $data['status'] ?? null,
            $data['note'] ?? null,
        );

        return redirect()->route('admin.attendance.index', ['date' => $attendance->date->toDateString()])->with('success', 'Attendance saved.');
    }

    public function edit(Attendance $attendance)
    {
        return view('admin.attendance.form', [
            'attendance' => $attendance->load('employee'),
            'employees' => collect([$attendance->employee_id => $attendance->employee->employee_code.' - '.$attendance->employee->name]),
        ]);
    }

    public function update(AttendanceRequest $request, Attendance $attendance)
    {
        $data = $request->validated();
        $this->service->saveManual(
            $attendance->employee,
            $attendance->date,
            $data['check_in'] ?? null,
            $data['check_out'] ?? null,
            $data['status'] ?? null,
            $data['note'] ?? null,
        );

        return redirect()->route('admin.attendance.index', ['date' => $attendance->date->toDateString()])->with('success', 'Attendance updated.');
    }

    public function reset(Attendance $attendance)
    {
        $this->service->resetToAutomatic($attendance);

        return back()->with('success', 'Attendance recalculated from device punches.');
    }

    public function process(ProcessAttendanceRequest $request)
    {
        $count = $this->service->processRange(Carbon::parse($request->from), Carbon::parse($request->to));

        return back()->with('success', "{$count} attendance records processed.");
    }
}
