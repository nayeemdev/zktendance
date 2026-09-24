@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Company</div>
                <div class="card-body row">
                    <x-input name="company_name" label="Company Name" :value="$settings['company_name']" class="col-12 mb-3" required />
                    <x-input name="company_email" type="email" label="Email" :value="$settings['company_email']" class="col-md-6 mb-3" />
                    <x-input name="company_phone" label="Phone" :value="$settings['company_phone']" class="col-md-6 mb-3" />
                    <x-input name="company_address" label="Address" :value="$settings['company_address']" class="col-12 mb-3" />
                    <x-input name="company_logo" type="file" label="Logo" class="col-12 mb-3" accept="image/*" help="Run php artisan storage:link once so the logo can be shown." />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Region</div>
                <div class="card-body row">
                    <x-select name="country" label="Country" :options="collect($countries)->map(fn ($c) => $c['name'])" :value="$settings['country']" class="col-md-6 mb-3" required />
                    <x-select name="timezone" label="Timezone" :options="array_combine($timezones, $timezones)" :value="$settings['timezone']" class="col-md-6 mb-3" required />
                    <x-input name="currency" label="Currency Code" :value="$settings['currency']" class="col-md-4 mb-3" required />
                    <x-input name="currency_symbol" label="Symbol" :value="$settings['currency_symbol']" class="col-md-4 mb-3" required />
                    <x-select name="fiscal_year_start_month" label="Fiscal Year Starts" :options="collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))])" :value="$settings['fiscal_year_start_month']" class="col-md-4 mb-3" required />
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Payroll Rules</div>
                <div class="card-body row">
                    <x-select name="salary_day_basis" label="Per Day Salary" :options="['calendar' => 'Salary / days in month', '30' => 'Salary / 30']" :value="$settings['salary_day_basis']" class="col-md-6 mb-3" required />
                    <x-select name="absent_deduction_base" label="Absent Deduction Based On" :options="['gross' => 'Gross Salary', 'basic' => 'Basic Salary']" :value="$settings['absent_deduction_base']" class="col-md-6 mb-3" required />
                    <x-select name="leave_approval_levels" label="Leave Approval" :options="[1 => 'One step: manager or HR approves', 2 => 'Two steps: manager recommends, HR approves']" :value="$settings['leave_approval_levels']" class="col-md-12 mb-3" required />
                    <x-input name="late_days_per_deduction" type="number" label="Late days for 1 day deduction" :value="$settings['late_days_per_deduction']" class="col-md-6 mb-3" required help="0 turns it off. 3 means every 3 lates cut 1 day." />
                    <x-input name="payslip_footer" label="Payslip Footer" :value="$settings['payslip_footer']" class="col-md-6 mb-3" />
                    <x-checkbox name="email_payslips" label="Email payslips automatically when payroll is approved" :checked="$settings['email_payslips']" class="col-12 mb-3" />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Notifications</div>
                <div class="card-body row">
                    <x-checkbox name="email_notifications" label="Also send notifications by email" :checked="$settings['email_notifications']" class="col-12 mb-3" help="Leave, correction and payslip updates are always shown in the app." />
                    <x-input name="device_offline_minutes" type="number" label="Alert when a device is silent for (minutes)" :value="$settings['device_offline_minutes']" class="col-md-6 mb-3" required />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Income Tax (TDS)</div>
                <div class="card-body">
                    <x-checkbox name="tax_enabled" label="Deduct monthly income tax" :checked="$settings['tax_enabled']" />
                    <div class="row">
                        <x-input name="tax_exempt_fraction" type="number" step="0.0001" label="Exempt fraction of income" :value="$settings['tax_exempt_fraction']" class="col-md-4 mb-3" required help="BD: 1/3 = 0.3333" />
                        <x-input name="tax_exempt_cap" type="number" label="Exemption cap" :value="$settings['tax_exempt_cap']" class="col-md-4 mb-3" required help="BD: 500000" />
                        <x-input name="minimum_tax" type="number" label="Minimum tax" :value="$settings['minimum_tax']" class="col-md-4 mb-3" required help="Applied when tax is above 0" />
                    </div>
                    <label class="form-label">Yearly tax slabs (in order)</label>
                    <table class="table table-sm">
                        <thead><tr><th>Slab amount</th><th>Rate %</th></tr></thead>
                        <tbody>
                        @foreach ($slabs->concat(array_fill(0, 2, null)) as $i => $slab)
                            <tr>
                                <td><input type="number" step="0.01" name="slabs[{{ $i }}][amount]" value="{{ $slab?->amount }}" class="form-control form-control-sm" placeholder="Empty = rest of income"></td>
                                <td><input type="number" step="0.01" name="slabs[{{ $i }}][rate]" value="{{ $slab?->rate }}" class="form-control form-control-sm"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="small text-muted mb-0">Defaults follow the Bangladesh slabs for FY 2026-27. Check the latest Finance Act and update them if they change. Clear a rate to remove a slab.</p>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary">Save Settings</button>
</form>
@endsection
