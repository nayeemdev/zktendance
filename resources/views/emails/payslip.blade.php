<p>Dear {{ $payslip->employee->name }},</p>
<p>Your payslip for {{ $payslip->payrollRun->month->format('F Y') }} is attached.</p>
<p>Net pay: <strong>{{ setting('currency') }} {{ money($payslip->net_salary, false) }}</strong></p>
<p>Regards,<br>{{ setting('company_name') }}</p>
