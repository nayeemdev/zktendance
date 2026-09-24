<table width="100%" style="margin-bottom: 12px">
    <tr>
        <td>
            <div style="font-size: 18px; font-weight: bold">{{ setting('company_name') }}</div>
            <div style="font-size: 12px; color: #666">{{ setting('company_address') }}</div>
        </td>
        <td style="text-align: right">
            <div style="font-size: 16px; font-weight: bold">PAYSLIP</div>
            <div style="font-size: 12px">{{ $payslip->payrollRun->month->format('F Y') }}</div>
        </td>
    </tr>
</table>

<table width="100%" class="table table-sm table-bordered" cellpadding="4" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px">
    <tr>
        <td><strong>Name</strong></td><td>{{ $payslip->employee->name }}</td>
        <td><strong>Employee ID</strong></td><td>{{ $payslip->employee->employee_code }}</td>
    </tr>
    <tr>
        <td><strong>Designation</strong></td><td>{{ $payslip->employee->designation?->name }}</td>
        <td><strong>Department</strong></td><td>{{ $payslip->employee->department?->name }}</td>
    </tr>
    <tr>
        <td><strong>Branch</strong></td><td>{{ $payslip->employee->branch?->name }}</td>
        <td><strong>Bank Account</strong></td><td>{{ $payslip->employee->bank_name }} {{ $payslip->employee->bank_account_no }}</td>
    </tr>
</table>

<table width="100%" class="table table-sm table-bordered text-center" cellpadding="4" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px; text-align: center">
    <tr style="background: #f2f2f2">
        <th>Days in Month</th><th>Payable Days</th><th>Present</th><th>Absent</th><th>Paid Leave</th><th>Unpaid Leave</th><th>Off Days</th><th>Late</th><th>OT Hours</th>
    </tr>
    <tr>
        <td>{{ $payslip->days_in_month }}</td><td>{{ $payslip->payable_days }}</td><td>{{ $payslip->present_days }}</td><td>{{ $payslip->absent_days }}</td>
        <td>{{ $payslip->paid_leave_days }}</td><td>{{ $payslip->unpaid_leave_days }}</td><td>{{ $payslip->off_days }}</td><td>{{ $payslip->late_days }}</td><td>{{ $payslip->overtime_hours }}</td>
    </tr>
</table>

<table width="100%" style="border-collapse: collapse; font-size: 12px">
    <tr>
        <td width="50%" valign="top" style="padding-right: 6px">
            <table width="100%" class="table table-sm table-bordered" cellpadding="4" style="border-collapse: collapse">
                <tr style="background: #e8f5e9"><th style="text-align: left">Earnings</th><th style="text-align: right">Amount</th></tr>
                @foreach ($payslip->earnings() as $item)
                    <tr><td>{{ $item->name }}</td><td style="text-align: right">{{ money($item->amount, false) }}</td></tr>
                @endforeach
                <tr style="font-weight: bold"><td>Total Earnings</td><td style="text-align: right">{{ money($payslip->total_earnings, false) }}</td></tr>
            </table>
        </td>
        <td width="50%" valign="top" style="padding-left: 6px">
            <table width="100%" class="table table-sm table-bordered" cellpadding="4" style="border-collapse: collapse">
                <tr style="background: #ffebee"><th style="text-align: left">Deductions</th><th style="text-align: right">Amount</th></tr>
                @forelse ($payslip->deductions() as $item)
                    <tr><td>{{ $item->name }}</td><td style="text-align: right">{{ money($item->amount, false) }}</td></tr>
                @empty
                    <tr><td colspan="2">None</td></tr>
                @endforelse
                <tr style="font-weight: bold"><td>Total Deductions</td><td style="text-align: right">{{ money($payslip->total_deductions, false) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div style="margin-top: 12px; padding: 10px; background: #f2f2f2; font-size: 15px; font-weight: bold; text-align: right">
    Net Pay: {{ setting('currency') }} {{ money($payslip->net_salary, false) }}
</div>

<p style="font-size: 11px; color: #777; margin-top: 16px">{{ setting('payslip_footer') }}</p>
