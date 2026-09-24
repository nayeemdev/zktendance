<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(
        private AttendanceLogService $logs,
        private LeaveService $leaves,
    ) {}

    public function create(array $data): Employee
    {
        $employee = DB::transaction(function () use ($data) {
            $employee = Employee::create(Arr::except($data, ['password', 'create_login']));
            $this->syncLogin($employee, $data);

            return $employee;
        });

        $this->logs->linkEmployee($employee);
        $this->leaves->allocateYear(now()->year, $employee);

        return $employee;
    }

    public function update(Employee $employee, array $data): Employee
    {
        DB::transaction(function () use ($employee, $data) {
            $employee->update(Arr::except($data, ['password', 'create_login']));
            $this->syncLogin($employee, $data);
        });

        $this->logs->linkEmployee($employee);

        return $employee;
    }

    private function syncLogin(Employee $employee, array $data): void
    {
        $user = $employee->user;

        if (! $user && empty($data['create_login'])) {
            return;
        }

        $attributes = [
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => $employee->status === 'active',
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        if ($user) {
            $user->update($attributes);

            return;
        }

        $user = User::create($attributes + [
            'role' => User::ROLE_EMPLOYEE,
            'password' => $data['password'] ?? str()->random(16),
        ]);
        $employee->update(['user_id' => $user->id]);
    }
}
