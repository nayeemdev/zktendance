<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest;
use App\Models\Branch;
use App\Models\Holiday;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $holidays = Holiday::with('branch')->whereYear('date', $year)->orderBy('date')->get();

        return view('admin.holidays.index', compact('holidays', 'year'));
    }

    public function create()
    {
        return view('admin.holidays.form', ['holiday' => new Holiday, 'branches' => Branch::pluck('name', 'id')]);
    }

    public function store(HolidayRequest $request)
    {
        $data = $request->validated();
        $start = Carbon::parse($data['date']);
        $end = Carbon::parse($data['end_date'] ?? $data['date']);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            Holiday::create(['name' => $data['name'], 'branch_id' => $data['branch_id'] ?? null, 'date' => $date->toDateString()]);
        }

        $this->attendance->processRange($start, $end);

        return redirect()->route('admin.holidays.index', ['year' => $start->year])->with('success', 'Holiday saved.');
    }

    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.form', ['holiday' => $holiday, 'branches' => Branch::pluck('name', 'id')]);
    }

    public function update(HolidayRequest $request, Holiday $holiday)
    {
        $old = $holiday->date->copy();
        $holiday->update($request->safe()->only(['name', 'branch_id', 'date']));

        $this->attendance->processDate($old);
        $this->attendance->processDate($holiday->date);

        return redirect()->route('admin.holidays.index', ['year' => $holiday->date->year])->with('success', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday)
    {
        $date = $holiday->date->copy();
        $holiday->delete();
        $this->attendance->processDate($date);

        return back()->with('success', 'Holiday deleted.');
    }
}
