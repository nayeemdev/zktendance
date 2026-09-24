<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function staff(string $title, string $message, ?string $url = null, array $roles = [User::ROLE_ADMIN, User::ROLE_HR]): void
    {
        $users = User::whereIn('role', $roles)->where('is_active', true)->get();

        Notification::send($users, new SystemNotification($title, $message, $url));
    }

    public function employee(Employee $employee, string $title, string $message, ?string $url = null, bool $sendMail = true): void
    {
        if ($employee->user && $employee->user->is_active) {
            $employee->user->notify(new SystemNotification($title, $message, $url, $sendMail));
        }
    }
}
