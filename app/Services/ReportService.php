<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportService
{
    public function __construct(private PayrollService $payroll) {}

    public function employees(array $filters): Collection
    {
        return Employee::with(['branch', 'department', 'designation'])
            ->active()
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')
            ->get();
    }

    public function monthlySummary(Carbon $month, array $filters): Collection
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth()->min(today());

        return $this->employees($filters)->map(function (Employee $employee) use ($start, $end) {
            $summary = $this->payroll->attendanceSummary($employee, $start, $end);
            $late = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('late_minutes');

            return ['employee' => $employee, 'late_minutes' => (int) $late] + $summary;
        });
    }

    public function monthlySheet(Carbon $month, array $filters): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $employees = $this->employees($filters);

        $attendances = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($row) => $row->date->day));

        return [
            'employees' => $employees,
            'attendances' => $attendances,
            'days' => $start->daysInMonth,
        ];
    }

    public function export(string $baseName, array $header, iterable $rows, ?string $format = 'csv')
    {
        return $format === 'xlsx'
            ? $this->xlsx($baseName.'.xlsx', $header, $rows)
            : $this->csv($baseName.'.csv', $header, $rows);
    }

    public function csv(string $fileName, array $header, iterable $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function xlsx(string $fileName, array $header, iterable $rows)
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValuesWithStyle($header, (new Style)->withFontBold(true)));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn ($value) => $value ?? '', array_values($row))));
        }
        $writer->close();

        return response()->download($path, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
