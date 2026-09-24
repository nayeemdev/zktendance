<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Services\EmployeeService;
use App\Services\LeaveService;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $service) {}

    public function index(Request $request)
    {
        $employees = Employee::with(['branch', 'department', 'designation', 'currentSalary'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('employee_code', 'like', "%{$s}%")
                ->orWhere('device_user_id', $s)))
            ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('employee_code')
            ->paginate(25)
            ->withQueryString();

        return view('admin.employees.index', $this->options() + compact('employees'));
    }

    public function create()
    {
        $employee = new Employee([
            'joining_date' => today(),
            'status' => 'active',
            'employment_type' => 'permanent',
            'tax_enabled' => true,
            'employee_code' => $this->service->nextCode(),
        ]);

        return view('admin.employees.form', $this->options() + compact('employee'));
    }

    public function store(EmployeeRequest $request)
    {
        $employee = $this->service->create($request->validated());

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee created. Add a salary to include them in payroll.');
    }

    public function show(Employee $employee, SalaryService $salary, LeaveService $leaves)
    {
        $employee->load(['branch', 'department', 'designation', 'shift', 'user', 'salaries.structure', 'loans', 'documents.uploader']);
        $current = $employee->salaries->first();

        return view('admin.employees.show', [
            'employee' => $employee,
            'breakdown' => $current ? $salary->breakdown($current->structure, $current->gross_salary) : [],
            'balances' => $employee->leaveBalances()->with('leaveType')->where('year', now()->year)->get(),
            'recent' => $employee->attendances()->latest('date')->limit(10)->get(),
            'structures' => SalaryStructure::pluck('name', 'id'),
        ]);
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.form', $this->options() + compact('employee'));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $this->service->update($employee, $request->validated());

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->user?->delete();
        $employee->delete();

        return redirect()->route('admin.employees.index')->with('success', 'Employee deleted.');
    }

    private function options(): array
    {
        return [
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'designations' => Designation::orderBy('name')->pluck('name', 'id'),
            'shifts' => Shift::orderBy('start_time')->get()->mapWithKeys(fn ($s) => [$s->id => $s->label()]),
        ];
    }
}
