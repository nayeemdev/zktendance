<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoanRequest;
use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = Loan::with('employee')
            ->when($request->input('status', 'active'), fn ($q, $status) => $status === 'all' ? $q : $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.loans.index', compact('loans'));
    }

    public function create()
    {
        return view('admin.loans.form', ['loan' => new Loan(['start_month' => today()->startOfMonth()]), 'employees' => $this->employees()]);
    }

    public function store(LoanRequest $request)
    {
        Loan::create($request->validated());

        return redirect()->route('admin.loans.index')->with('success', 'Loan created.');
    }

    public function edit(Loan $loan)
    {
        return view('admin.loans.form', ['loan' => $loan, 'employees' => $this->employees()]);
    }

    public function update(LoanRequest $request, Loan $loan)
    {
        $loan->update($request->validated());

        return redirect()->route('admin.loans.index')->with('success', 'Loan updated.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return back()->with('success', 'Loan deleted.');
    }

    private function employees()
    {
        return Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]);
    }
}
