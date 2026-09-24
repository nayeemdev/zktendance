<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\LeaveType;
use App\Models\OvertimeRule;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\TaxSlab;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetupService
{
    public const COUNTRIES = [
        'BD' => ['name' => 'Bangladesh', 'currency' => 'BDT', 'symbol' => '৳', 'timezone' => 'Asia/Dhaka', 'weekend' => [5], 'fiscal_month' => 7],
        'IN' => ['name' => 'India', 'currency' => 'INR', 'symbol' => '₹', 'timezone' => 'Asia/Kolkata', 'weekend' => [0], 'fiscal_month' => 4],
        'PK' => ['name' => 'Pakistan', 'currency' => 'PKR', 'symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'weekend' => [0], 'fiscal_month' => 7],
        'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'symbol' => 'AED', 'timezone' => 'Asia/Dubai', 'weekend' => [0, 6], 'fiscal_month' => 1],
        'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'symbol' => 'SAR', 'timezone' => 'Asia/Riyadh', 'weekend' => [5, 6], 'fiscal_month' => 1],
        'US' => ['name' => 'United States', 'currency' => 'USD', 'symbol' => '$', 'timezone' => 'America/New_York', 'weekend' => [0, 6], 'fiscal_month' => 1],
    ];

    public function __construct(private SettingService $settings) {}

    public function run(array $data): User
    {
        $country = self::COUNTRIES[$data['country']] ?? self::COUNTRIES['BD'];

        return DB::transaction(function () use ($data, $country) {
            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => User::ROLE_ADMIN,
            ]);

            Branch::create([
                'name' => $data['branch_name'],
                'code' => 'HO',
                'address' => $data['company_address'] ?? null,
                'weekend_days' => $country['weekend'],
            ]);

            Shift::create([
                'name' => 'General',
                'start_time' => $data['office_start'],
                'end_time' => $data['office_end'],
                'grace_minutes' => 15,
                'half_day_minutes' => 240,
                'break_minutes' => 60,
                'is_default' => true,
            ]);

            $this->seedDefaults($data['country']);

            $this->settings->set([
                'company_name' => $data['company_name'],
                'company_address' => $data['company_address'] ?? '',
                'company_phone' => $data['company_phone'] ?? '',
                'company_email' => $data['company_email'] ?? '',
                'country' => $data['country'],
                'currency' => $data['currency'] ?? $country['currency'],
                'currency_symbol' => $data['currency_symbol'] ?? $country['symbol'],
                'timezone' => $data['timezone'] ?? $country['timezone'],
                'fiscal_year_start_month' => $country['fiscal_month'],
                'tax_enabled' => $data['country'] === 'BD',
                'setup_completed' => true,
            ]);

            return $admin;
        });
    }

    public function seedDefaults(string $country = 'BD'): void
    {
        foreach ([
            ['name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 10, 'is_paid' => true, 'carry_forward_limit' => 0],
            ['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 14, 'is_paid' => true, 'carry_forward_limit' => 0],
            ['name' => 'Earned Leave', 'code' => 'EL', 'days_per_year' => 18, 'is_paid' => true, 'carry_forward_limit' => 40, 'allow_half_day' => false],
            ['name' => 'Maternity Leave', 'code' => 'ML', 'days_per_year' => 112, 'is_paid' => true, 'carry_forward_limit' => 0, 'allow_half_day' => false],
            ['name' => 'Leave Without Pay', 'code' => 'LWP', 'days_per_year' => 0, 'is_paid' => false, 'carry_forward_limit' => 0],
        ] as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }

        $components = [];
        foreach ([
            ['name' => 'Basic Salary', 'type' => 'earning', 'is_basic' => true, 'sort_order' => 1],
            ['name' => 'House Rent', 'type' => 'earning', 'sort_order' => 2],
            ['name' => 'Medical Allowance', 'type' => 'earning', 'sort_order' => 3],
            ['name' => 'Conveyance', 'type' => 'earning', 'sort_order' => 4],
            ['name' => 'Provident Fund', 'type' => 'deduction', 'is_taxable' => false, 'sort_order' => 10],
        ] as $component) {
            $components[$component['name']] = SalaryComponent::firstOrCreate(['name' => $component['name']], $component);
        }

        if (! SalaryStructure::exists()) {
            $standard = SalaryStructure::create(['name' => 'Standard', 'description' => 'Basic 50%, House Rent 25%, Medical 15%, Conveyance 10% of gross']);
            $standard->items()->createMany([
                ['salary_component_id' => $components['Basic Salary']->id, 'calculation' => 'percent_of_gross', 'value' => 50],
                ['salary_component_id' => $components['House Rent']->id, 'calculation' => 'percent_of_gross', 'value' => 25],
                ['salary_component_id' => $components['Medical Allowance']->id, 'calculation' => 'percent_of_gross', 'value' => 15],
                ['salary_component_id' => $components['Conveyance']->id, 'calculation' => 'percent_of_gross', 'value' => 10],
            ]);
        }

        OvertimeRule::firstOrCreate(['branch_id' => null], [
            'name' => 'Default Overtime Rule',
            'min_minutes' => 30,
            'rounding_minutes' => 15,
            'max_daily_minutes' => 240,
            'rate_base' => 'basic',
            'monthly_hours_divisor' => 208,
            'workday_multiplier' => 2,
            'offday_multiplier' => 2,
        ]);

        if ($country === 'BD' && ! TaxSlab::exists()) {
            foreach ([[375000, 0], [300000, 10], [400000, 15], [500000, 20], [2000000, 25], [null, 30]] as $i => [$amount, $rate]) {
                TaxSlab::create(['amount' => $amount, 'rate' => $rate, 'sort_order' => $i + 1]);
            }
        }
    }
}
