<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftAssignmentRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\RosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RosterController extends Controller
{
    public function __construct(private RosterService $service) {}

    public function index(Request $request)
    {
        $start = Carbon::parse($request->input('week', today()->toDateString()))->startOfWeek(Carbon::SATURDAY);
        $filters = $request->only(['branch_id', 'department_id']);

        return view('admin.roster.index', $this->service->week($start, $filters) + [
            'start' => $start,
            'branches' => Branch::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'assignments' => ShiftAssignment::with(['employee', 'shift'])
                ->whereDate('end_date', '>=', $start)
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function create()
    {
        return view('admin.roster.create', [
            'employees' => Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
            'branches' => Branch::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'shifts' => Shift::orderBy('start_time')->get()->mapWithKeys(fn ($s) => [$s->id => $s->label()]),
        ]);
    }

    public function store(ShiftAssignmentRequest $request)
    {
        $count = $this->service->assign($request->validated());

        return redirect()->route('admin.roster.index', ['week' => $request->start_date])->with('success', "{$count} shift assignments saved.");
    }

    public function destroy(ShiftAssignment $assignment)
    {
        $this->service->remove($assignment);

        return back()->with('success', 'Shift assignment removed.');
    }
}
