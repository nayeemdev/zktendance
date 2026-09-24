<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayrollAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $adjustments = PayrollAdjustment::with('employee')->whereDate('month', $month)->latest()->get();

        return view('admin.adjustments.index', compact('adjustments', 'month'));
    }

    public function create()
    {
        return view('admin.adjustments.form', [
            'adjustment' => new PayrollAdjustment(['month' => today()->startOfMonth(), 'type' => 'earning']),
            'employees' => Employee::active()->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
        ]);
    }

    public function store(PayrollAdjustmentRequest $request)
    {
        $data = $request->validated();
        foreach ($data['employee_ids'] as $employeeId) {
            PayrollAdjustment::create([
                'employee_id' => $employeeId,
                'month' => $data['month'].'-01',
                'type' => $data['type'],
                'title' => $data['title'],
                'amount' => $data['amount'],
            ]);
        }

        return redirect()->route('admin.adjustments.index', ['month' => $data['month']])->with('success', 'Adjustment added. Regenerate the draft payroll to apply it.');
    }

    public function destroy(PayrollAdjustment $adjustment)
    {
        $adjustment->delete();

        return back()->with('success', 'Adjustment deleted.');
    }
}
