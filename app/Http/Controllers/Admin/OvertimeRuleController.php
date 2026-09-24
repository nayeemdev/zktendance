<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimeRuleRequest;
use App\Models\Branch;
use App\Models\OvertimeRule;

class OvertimeRuleController extends Controller
{
    public function index()
    {
        return view('admin.overtime-rules.index', ['rules' => OvertimeRule::with('branch')->orderBy('branch_id')->get()]);
    }

    public function create()
    {
        return view('admin.overtime-rules.form', [
            'rule' => new OvertimeRule(['is_enabled' => true, 'min_minutes' => 30, 'rounding_minutes' => 15, 'max_daily_minutes' => 240, 'rate_base' => 'basic', 'monthly_hours_divisor' => 208, 'workday_multiplier' => 2, 'offday_multiplier' => 2]),
            'branches' => Branch::pluck('name', 'id'),
        ]);
    }

    public function store(OvertimeRuleRequest $request)
    {
        OvertimeRule::create($request->validated());

        return redirect()->route('admin.overtime-rules.index')->with('success', 'Overtime rule created.');
    }

    public function edit(OvertimeRule $overtimeRule)
    {
        return view('admin.overtime-rules.form', ['rule' => $overtimeRule, 'branches' => Branch::pluck('name', 'id')]);
    }

    public function update(OvertimeRuleRequest $request, OvertimeRule $overtimeRule)
    {
        $overtimeRule->update($request->validated());

        return redirect()->route('admin.overtime-rules.index')->with('success', 'Overtime rule updated.');
    }

    public function destroy(OvertimeRule $overtimeRule)
    {
        $overtimeRule->delete();

        return back()->with('success', 'Overtime rule deleted.');
    }
}
