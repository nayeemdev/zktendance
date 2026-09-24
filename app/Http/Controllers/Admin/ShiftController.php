<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftRequest;
use App\Models\Shift;

class ShiftController extends Controller
{
    public function index()
    {
        return view('admin.shifts.index', ['shifts' => Shift::orderBy('start_time')->get()]);
    }

    public function create()
    {
        return view('admin.shifts.form', ['shift' => new Shift(['grace_minutes' => 15, 'half_day_minutes' => 240, 'break_minutes' => 60, 'early_leave_grace_minutes' => 0])]);
    }

    public function store(ShiftRequest $request)
    {
        $this->save(new Shift, $request->validated());

        return redirect()->route('admin.shifts.index')->with('success', 'Shift created.');
    }

    public function edit(Shift $shift)
    {
        return view('admin.shifts.form', compact('shift'));
    }

    public function update(ShiftRequest $request, Shift $shift)
    {
        $this->save($shift, $request->validated());

        return redirect()->route('admin.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->is_default) {
            return back()->with('error', 'The default shift cannot be deleted.');
        }

        $shift->delete();

        return back()->with('success', 'Shift deleted.');
    }

    private function save(Shift $shift, array $data): void
    {
        if (! empty($data['is_default'])) {
            Shift::where('is_default', true)->update(['is_default' => false]);
        }

        $shift->fill($data)->save();
    }
}
