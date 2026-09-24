<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected User $admin;

    protected function setUpCompany(): void
    {
        $this->admin = app(SetupService::class)->run([
            'company_name' => 'Test Company',
            'country' => 'BD',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'timezone' => 'UTC',
            'branch_name' => 'Head Office',
            'office_start' => '09:00',
            'office_end' => '18:00',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password',
        ]);
    }

    protected function makeEmployee(array $attributes = [], ?float $gross = null): Employee
    {
        static $n = 0;
        $n++;

        $employee = Employee::create($attributes + [
            'branch_id' => Branch::first()->id,
            'shift_id' => Shift::first()->id,
            'employee_code' => 'EMP'.$n,
            'device_user_id' => (string) (100 + $n),
            'name' => 'Employee '.$n,
            'joining_date' => '2025-01-01',
        ]);

        if ($gross) {
            $employee->salaries()->create([
                'salary_structure_id' => SalaryStructure::first()->id,
                'gross_salary' => $gross,
                'effective_from' => '2025-01-01',
            ]);
        }

        return $employee;
    }
}
