<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Loan;
use App\Models\Notice;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\AttendanceLogService;
use App\Services\LeaveService;
use App\Services\SetupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(SetupService $setup, AttendanceLogService $logs, LeaveService $leaves): void
    {
        $admin = $setup->run([
            'company_name' => 'Demo Office Ltd',
            'company_address' => 'House 10, Road 5, Dhanmondi, Dhaka',
            'country' => 'BD',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'timezone' => 'Asia/Dhaka',
            'branch_name' => 'Head Office',
            'office_start' => '09:00',
            'office_end' => '18:00',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password',
        ]);

        User::create(['name' => 'HR Manager', 'email' => 'hr@example.com', 'password' => 'password', 'role' => User::ROLE_HR]);

        $head = Branch::first();
        $ctg = Branch::create(['name' => 'Chattogram Branch', 'code' => 'CTG', 'address' => 'Agrabad, Chattogram', 'weekend_days' => [5, 6]]);

        $departments = collect(['Administration', 'Accounts', 'Engineering', 'Sales'])->map(fn ($name) => Department::create(['name' => $name]));
        $designations = collect(['Manager', 'Senior Executive', 'Executive', 'Software Engineer', 'Office Assistant'])->map(fn ($name) => Designation::create(['name' => $name]));

        Device::create(['branch_id' => $head->id, 'name' => 'Head Office Gate', 'model' => 'K40', 'connection_mode' => 'pull', 'ip_address' => '192.168.1.201', 'port' => 4370]);
        Device::create(['branch_id' => $ctg->id, 'name' => 'CTG Face Terminal', 'model' => 'SpeedFace-V5L', 'connection_mode' => 'push', 'serial_number' => 'DEMO0001']);

        $structure = SalaryStructure::first();
        $names = ['Rahim Uddin', 'Karim Hossain', 'Nusrat Jahan', 'Tanvir Ahmed', 'Farhana Akter', 'Sabbir Rahman', 'Mitu Islam', 'Arif Chowdhury'];
        $employees = collect($names)->map(function ($name, $i) use ($head, $ctg, $departments, $designations, $structure) {
            $n = $i + 1;
            $user = User::create(['name' => $name, 'email' => "employee{$n}@example.com", 'password' => 'password', 'role' => User::ROLE_EMPLOYEE]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'branch_id' => $n <= 6 ? $head->id : $ctg->id,
                'department_id' => $departments[$i % 4]->id,
                'designation_id' => $designations[$i % 5]->id,
                'employee_code' => sprintf('EMP%04d', $n),
                'device_user_id' => (string) $n,
                'name' => $name,
                'email' => "employee{$n}@example.com",
                'phone' => '0171100000'.$n,
                'gender' => in_array($n, [3, 5, 7]) ? 'female' : 'male',
                'joining_date' => '2024-01-01',
                'bank_name' => 'Dutch-Bangla Bank',
                'bank_account_no' => '1011000000'.$n,
            ]);

            $employee->salaries()->create([
                'salary_structure_id' => $structure->id,
                'gross_salary' => [80000, 45000, 35000, 60000, 30000, 25000, 40000, 20000][$i],
                'effective_from' => '2024-01-01',
            ]);

            return $employee;
        });

        Holiday::create(['name' => 'National Mourning Day', 'date' => now()->subMonth()->startOfMonth()->addDays(14)->toDateString()]);
        Notice::create(['title' => 'Welcome to the new attendance system', 'body' => "Please punch in and out every day.\nApply for leave from your portal.", 'published_on' => today()]);

        $leaves->allocateYear(now()->year);

        mt_srand(42);
        $records = [];
        $start = now()->subMonth()->startOfMonth();
        for ($date = $start->copy(); $date->lt(today()); $date->addDay()) {
            foreach ($employees as $employee) {
                if ($employee->branch->isWeekend($date) || mt_rand(1, 100) <= 4) {
                    continue;
                }

                $in = $date->copy()->setTime(8, 40)->addMinutes(mt_rand(0, 45));
                $out = $date->copy()->setTime(17, 55)->addMinutes(mt_rand(0, 120));
                $records[] = ['user_id' => $employee->device_user_id, 'timestamp' => $in->toDateTimeString(), 'type' => 1];
                $records[] = ['user_id' => $employee->device_user_id, 'timestamp' => $out->toDateTimeString(), 'type' => 1];
            }
        }
        $logs->store(Device::first(), $records);

        $casual = LeaveType::where('code', 'CL')->first();
        $start = $this->workingDay($employees[1], today()->addDays(3));
        $leave = $leaves->apply($employees[1], ['leave_type_id' => $casual->id, 'start_date' => $start->toDateString(), 'end_date' => $this->workingDay($employees[1], $start->copy()->addDay())->toDateString(), 'reason' => 'Family program']);
        $leaves->approve($leave, $admin);
        $day = $this->workingDay($employees[2], today()->addDays(7))->toDateString();
        $leaves->apply($employees[2], ['leave_type_id' => $casual->id, 'start_date' => $day, 'end_date' => $day, 'reason' => 'Personal work']);

        Loan::create(['employee_id' => $employees[3]->id, 'amount' => 30000, 'installment' => 5000, 'start_month' => Carbon::now()->startOfMonth(), 'reason' => 'Salary advance']);
    }

    private function workingDay(Employee $employee, Carbon $date): Carbon
    {
        while ($employee->branch->isWeekend($date) || Holiday::whereDate('date', $date)->exists()) {
            $date = $date->copy()->addDay();
        }

        return $date;
    }
}
