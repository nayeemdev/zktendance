<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeSalaryRequest;
use App\Models\Employee;
use App\Models\EmployeeSalary;

class EmployeeSalaryController extends Controller
{
    public function store(EmployeeSalaryRequest $request, Employee $employee)
    {
        $employee->salaries()->create($request->validated());

        return back()->with('success', 'Salary saved.');
    }

    public function destroy(EmployeeSalary $salary)
    {
        $salary->delete();

        return back()->with('success', 'Salary record deleted.');
    }
}
