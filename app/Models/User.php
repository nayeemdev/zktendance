<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_HR = 'hr';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EMPLOYEE = 'employee';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_HR => 'HR',
        self::ROLE_MANAGER => 'Branch Manager',
        self::ROLE_EMPLOYEE => 'Employee',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'branch_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function managedBranchId(): ?int
    {
        return $this->isManager() ? $this->branch_id : null;
    }

    public function canManageEmployee(Employee $employee): bool
    {
        return $this->isStaff() || ($this->isManager() && $employee->branch_id === $this->branch_id);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_HR]);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }
}
