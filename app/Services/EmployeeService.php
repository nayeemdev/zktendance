<?php

namespace App\Services;

use App\Models\Device;
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
            $employee = Employee::create(Arr::except($data, ['password', 'create_login', 'login_role']));
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
            $employee->update(Arr::except($data, ['password', 'create_login', 'login_role']));
            $this->syncLogin($employee, $data);
        });

        $this->logs->linkEmployee($employee);

        return $employee;
    }

    /**
     * @param  array<int, string>  $userIds
     */
    public function importFromDevice(Device $device, array $userIds): int
    {
        $rows = $device->users()->whereIn('user_id', $userIds)->get();
        $taken = Employee::whereIn('device_user_id', $rows->pluck('user_id'))->pluck('device_user_id')->flip();
        $count = 0;

        foreach ($rows as $row) {
            if (isset($taken[$row->user_id])) {
                continue;
            }

            $this->create([
                'employee_code' => $this->nextCode(),
                'device_user_id' => $row->user_id,
                'name' => $row->name ?: 'Device User '.$row->user_id,
                'branch_id' => $device->branch_id,
                'joining_date' => today()->toDateString(),
            ]);
            $count++;
        }

        return $count;
    }

    public function linkDeviceUser(Employee $employee, string $userId): void
    {
        Employee::where('device_user_id', $userId)->whereKeyNot($employee->id)->update(['device_user_id' => null]);
        $employee->update(['device_user_id' => $userId]);
        $this->logs->linkEmployee($employee);
    }

    public function nextCode(): string
    {
        $next = (int) Employee::max('id') + 1;
        do {
            $code = 'EMP'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (Employee::where('employee_code', $code)->exists());

        return $code;
    }

    private function syncLogin(Employee $employee, array $data): void
    {
        $user = $employee->user;

        if (! $user && empty($data['create_login'])) {
            return;
        }

        $manager = ($data['login_role'] ?? null) === User::ROLE_MANAGER;
        $attributes = [
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => $employee->status === 'active',
        ];

        if (! $user || ! $user->isStaff()) {
            $attributes['role'] = $manager ? User::ROLE_MANAGER : User::ROLE_EMPLOYEE;
            $attributes['branch_id'] = $manager ? $employee->branch_id : null;
        }

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        if ($user) {
            $user->update($attributes);

            return;
        }

        $user = User::create($attributes + [
            'password' => $data['password'] ?? str()->random(16),
        ]);
        $employee->update(['user_id' => $user->id]);
    }
}
