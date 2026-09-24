<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Services\ReportService;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index()
    {
        return view('admin.reports.index');
    }

    public function monthlySummary(ReportRequest $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $rows = $this->reports->monthlySummary($month, $request->validated());

        if ($request->filled('export')) {
            return $this->reports->export('attendance-summary-'.$month->format('Y-m'),
                ['Code', 'Name', 'Branch', 'Department', 'Present', 'Late', 'Absent', 'Paid Leave', 'Unpaid Leave', 'Off Days', 'Late Minutes', 'OT Hours'],
                $rows->map(fn ($r) => [
                    $r['employee']->employee_code, $r['employee']->name, $r['employee']->branch?->name, $r['employee']->department?->name,
                    $r['present'], $r['late'], $r['absent'], $r['leave'], $r['unpaid_leave'], $r['off'], $r['late_minutes'],
                    round(($r['ot_workday_minutes'] + $r['ot_offday_minutes']) / 60, 2),
                ]), $request->export);
        }

        return view('admin.reports.monthly-summary', $this->filters() + compact('rows', 'month'));
    }

    public function monthlySheet(ReportRequest $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        $sheet = $this->reports->monthlySheet($month, $request->validated());

        if ($request->filled('export')) {
            $codes = [Attendance::PRESENT => 'P', Attendance::LATE => 'L', Attendance::HALF_DAY => 'HD', Attendance::ABSENT => 'A', Attendance::LEAVE => 'LV', Attendance::UNPAID_LEAVE => 'UL', Attendance::HOLIDAY => 'H', Attendance::WEEKEND => 'W'];

            return $this->reports->export('attendance-sheet-'.$month->format('Y-m'),
                array_merge(['Code', 'Name'], range(1, $sheet['days'])),
                $sheet['employees']->map(function ($e) use ($sheet, $codes) {
                    $row = [$e->employee_code, $e->name];
                    for ($d = 1; $d <= $sheet['days']; $d++) {
                        $status = $sheet['attendances'][$e->id][$d]->status ?? null;
                        $row[] = $status ? $codes[$status] : '';
                    }

                    return $row;
                }), $request->export);
        }

        return view('admin.reports.monthly-sheet', $this->filters() + $sheet + compact('month'));
    }

    public function late(ReportRequest $request)
    {
        $from = Carbon::parse($request->input('from', today()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', today()->toDateString()));

        $rows = Attendance::with('employee.department')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where(fn ($q) => $q->where('late_minutes', '>', 0)->orWhere('early_leave_minutes', '>', 0))
            ->whereHas('employee', fn ($q) => $q
                ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
                ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id)))
            ->orderBy('date')
            ->get();

        if ($request->filled('export')) {
            return $this->reports->export('late-early-report',
                ['Date', 'Code', 'Name', 'Check In', 'Check Out', 'Late Minutes', 'Early Leave Minutes'],
                $rows->map(fn ($r) => [$r->date->toDateString(), $r->employee->employee_code, $r->employee->name, $r->check_in?->format('H:i'), $r->check_out?->format('H:i'), $r->late_minutes, $r->early_leave_minutes]), $request->export);
        }

        return view('admin.reports.late', $this->filters() + compact('rows', 'from', 'to'));
    }

    private function filters(): array
    {
        return [
            'branches' => Branch::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
        ];
    }
}
