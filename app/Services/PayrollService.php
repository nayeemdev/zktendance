<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\OvertimeRule;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private AttendanceService $attendance,
        private SalaryService $salary,
        private TaxService $tax,
    ) {}

    public function create(string $month, ?int $branchId, User $user): PayrollRun
    {
        $month = Carbon::parse($month.'-01')->startOfMonth();

        $conflict = PayrollRun::whereDate('month', $month)
            ->where(fn ($q) => $branchId ? $q->whereNull('branch_id')->orWhere('branch_id', $branchId) : $q)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['month' => 'A payroll run already exists for this month and branch.']);
        }

        $run = PayrollRun::create([
            'month' => $month,
            'branch_id' => $branchId,
            'created_by' => $user->id,
        ]);

        $this->generate($run);

        return $run;
    }

    public function generate(PayrollRun $run): void
    {
        if (! $run->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft payroll can be regenerated.']);
        }

        $start = $run->month->copy()->startOfMonth();
        $end = $run->month->copy()->endOfMonth();

        $employees = Employee::with(['branch'])
            ->whereIn('status', ['active', 'resigned', 'terminated'])
            ->whereDate('joining_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('leaving_date')->orWhereDate('leaving_date', '>=', $start))
            ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id))
            ->get();

        $this->attendance->processRange($start, $end, $employees->pluck('id')->all());

        DB::transaction(function () use ($run, $employees) {
            $run->payslips()->delete();

            foreach ($employees as $employee) {
                $this->createPayslip($run, $employee);
            }

            $this->refreshTotals($run);
        });
    }

    public function createPayslip(PayrollRun $run, Employee $employee): ?Payslip
    {
        $monthStart = $run->month->copy()->startOfMonth();
        $monthEnd = $run->month->copy()->endOfMonth();
        $salary = $employee->salaryOn($monthEnd);

        if (! $salary) {
            return null;
        }

        $periodStart = $employee->joining_date->gt($monthStart) ? $employee->joining_date->copy() : $monthStart;
        $periodEnd = $employee->leaving_date && $employee->leaving_date->lt($monthEnd) ? $employee->leaving_date->copy() : $monthEnd;

        $daysInMonth = $monthStart->daysInMonth;
        $employedDays = (int) $periodStart->diffInDays($periodEnd) + 1;
        $prorate = $employedDays / $daysInMonth;

        $summary = $this->attendanceSummary($employee, $periodStart, $periodEnd);
        $breakdown = $this->salary->breakdown($salary->structure, $salary->gross_salary);
        $basic = $this->salary->basic($breakdown);
        $gross = $this->salary->totalEarnings($breakdown) ?: $salary->gross_salary;

        $items = [];
        foreach ($breakdown as $line) {
            $items[] = ['type' => $line['type'], 'name' => $line['name'], 'amount' => round($line['amount'] * $prorate, 2)];
        }

        $basisDays = setting('salary_day_basis') === '30' ? 30 : $daysInMonth;
        $perDay = (setting('absent_deduction_base') === 'basic' ? $basic : $gross) / $basisDays;

        $latePerDeduction = (int) setting('late_days_per_deduction', 0);
        $latePenaltyDays = $latePerDeduction > 0 ? intdiv($summary['late'], $latePerDeduction) : 0;

        if ($summary['absent'] + $summary['unpaid_leave'] > 0) {
            $items[] = [
                'type' => SalaryComponent::DEDUCTION,
                'name' => 'Absent / Unpaid Leave ('.($summary['absent'] + $summary['unpaid_leave']).' days)',
                'amount' => round(($summary['absent'] + $summary['unpaid_leave']) * $perDay, 2),
            ];
        }

        if ($latePenaltyDays > 0) {
            $items[] = [
                'type' => SalaryComponent::DEDUCTION,
                'name' => "Late Deduction ({$summary['late']} lates = {$latePenaltyDays} days)",
                'amount' => round($latePenaltyDays * $perDay, 2),
            ];
        }

        $overtimeHours = 0;
        $rule = OvertimeRule::forBranch($employee->branch_id);
        if ($rule?->is_enabled && ($summary['ot_workday_minutes'] + $summary['ot_offday_minutes']) > 0) {
            $hourly = ($rule->rate_base === 'gross' ? $gross : $basic) / max(1, $rule->monthly_hours_divisor);
            $workdayHours = $summary['ot_workday_minutes'] / 60;
            $offdayHours = $summary['ot_offday_minutes'] / 60;
            $overtimeHours = round($workdayHours + $offdayHours, 2);

            $items[] = [
                'type' => SalaryComponent::EARNING,
                'name' => "Overtime ({$overtimeHours} hrs)",
                'amount' => round($hourly * ($workdayHours * $rule->workday_multiplier + $offdayHours * $rule->offday_multiplier), 2),
            ];
        }

        foreach (PayrollAdjustment::where('employee_id', $employee->id)->whereDate('month', $monthStart)->get() as $adjustment) {
            $items[] = ['type' => $adjustment->type, 'name' => $adjustment->title, 'amount' => $adjustment->amount];
        }

        $loans = Loan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->whereDate('start_month', '<=', $monthEnd)
            ->get();
        foreach ($loans as $loan) {
            $amount = min($loan->installment, $loan->remaining());
            if ($amount > 0) {
                $items[] = ['type' => SalaryComponent::DEDUCTION, 'name' => 'Loan Installment', 'amount' => $amount, 'loan_id' => $loan->id];
            }
        }

        if (setting('tax_enabled') && $employee->tax_enabled) {
            $taxable = collect($breakdown)->where('type', SalaryComponent::EARNING)->where('is_taxable', true)->sum('amount');
            $monthlyTax = $this->tax->monthlyTax($taxable);
            if ($monthlyTax > 0) {
                $items[] = ['type' => SalaryComponent::DEDUCTION, 'name' => 'Income Tax (TDS)', 'amount' => round($monthlyTax * $prorate, 2)];
            }
        }

        $earnings = round(collect($items)->where('type', SalaryComponent::EARNING)->sum('amount'), 2);
        $deductions = round(collect($items)->where('type', SalaryComponent::DEDUCTION)->sum('amount'), 2);

        $payslip = $run->payslips()->create([
            'employee_id' => $employee->id,
            'days_in_month' => $daysInMonth,
            'payable_days' => max(0, $employedDays - $summary['absent'] - $summary['unpaid_leave'] - $latePenaltyDays),
            'present_days' => $summary['present'],
            'absent_days' => $summary['absent'],
            'paid_leave_days' => $summary['leave'],
            'unpaid_leave_days' => $summary['unpaid_leave'],
            'off_days' => $summary['off'],
            'late_days' => $summary['late'],
            'overtime_hours' => $overtimeHours,
            'gross_salary' => $gross,
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'net_salary' => max(0, round($earnings - $deductions, 2)),
        ]);

        $payslip->items()->createMany(array_filter($items, fn ($item) => $item['amount'] > 0));

        return $payslip;
    }

    public function approve(PayrollRun $run, User $user): void
    {
        if (! $run->isDraft()) {
            throw ValidationException::withMessages(['status' => 'This payroll is already approved.']);
        }

        DB::transaction(function () use ($run, $user) {
            $run->load('payslips.items');

            foreach ($run->payslips as $payslip) {
                foreach ($payslip->items->whereNotNull('loan_id') as $item) {
                    $loan = Loan::find($item->loan_id);
                    if ($loan) {
                        $loan->increment('paid_amount', $item->amount);
                        if ($loan->fresh()->remaining() <= 0) {
                            $loan->update(['status' => 'completed']);
                        }
                    }
                }
            }

            $run->update(['status' => PayrollRun::APPROVED, 'approved_by' => $user->id, 'approved_at' => now()]);
        });
    }

    public function markPaid(PayrollRun $run): void
    {
        if ($run->status !== PayrollRun::APPROVED) {
            throw ValidationException::withMessages(['status' => 'Approve the payroll before marking it paid.']);
        }

        $run->update(['status' => PayrollRun::PAID, 'paid_at' => now()]);
    }

    public function refreshTotals(PayrollRun $run): void
    {
        $run->update([
            'total_gross' => $run->payslips()->sum('total_earnings'),
            'total_deductions' => $run->payslips()->sum('total_deductions'),
            'total_net' => $run->payslips()->sum('net_salary'),
        ]);
    }

    public function attendanceSummary(Employee $employee, Carbon $from, Carbon $to): array
    {
        $rows = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'unpaid_leave' => 0, 'off' => 0, 'late' => 0, 'ot_workday_minutes' => 0, 'ot_offday_minutes' => 0];

        foreach ($rows as $row) {
            match ($row->status) {
                Attendance::PRESENT => $summary['present']++,
                Attendance::LATE => [$summary['present']++, $summary['late']++],
                Attendance::HALF_DAY => [$summary['present'] += 0.5, $summary['absent'] += 0.5],
                Attendance::ABSENT => $summary['absent']++,
                Attendance::LEAVE => $summary['leave']++,
                Attendance::UNPAID_LEAVE => $summary['unpaid_leave']++,
                default => $summary['off']++,
            };

            if (in_array($row->status, [Attendance::HOLIDAY, Attendance::WEEKEND])) {
                $summary['ot_offday_minutes'] += $row->overtime_minutes;
            } else {
                $summary['ot_workday_minutes'] += $row->overtime_minutes;
            }
        }

        return $summary;
    }
}
