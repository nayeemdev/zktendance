<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\LeaveService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BranchManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Branch $other;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
        $this->other = Branch::create(['name' => 'Sylhet', 'code' => 'SYL', 'weekend_days' => [5]]);
        $this->manager = User::create(['name' => 'Manager', 'email' => 'm@example.com', 'password' => 'password', 'role' => 'manager', 'branch_id' => Branch::first()->id]);
    }

    private function leaveFor($employee): LeaveRequest
    {
        return app(LeaveService::class)->apply($employee, [
            'leave_type_id' => LeaveType::where('code', 'CL')->value('id'),
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-21',
        ]);
    }

    public function test_manager_only_sees_and_acts_on_own_branch(): void
    {
        $own = $this->makeEmployee(['name' => 'Own Person']);
        $foreign = $this->makeEmployee(['name' => 'Foreign Person', 'branch_id' => $this->other->id]);
        $ownLeave = $this->leaveFor($own);
        $foreignLeave = $this->leaveFor($foreign);

        $this->actingAs($this->manager)->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->manager)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->manager)->get(route('admin.leaves.index'))->assertSee('Own Person')->assertDontSee('Foreign Person');
        $this->actingAs($this->manager)->get(route('admin.attendance.index'))->assertOk();
        $this->actingAs($this->manager)->get(route('admin.corrections.index'))->assertOk();

        $this->actingAs($this->manager)->post(route('admin.leaves.approve', $foreignLeave))->assertForbidden();
        $this->actingAs($this->manager)->post(route('admin.leaves.approve', $ownLeave))->assertSessionHas('success');
        $this->assertSame('approved', $ownLeave->fresh()->status);
    }

    public function test_manager_cannot_open_hr_pages(): void
    {
        $this->actingAs($this->manager)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_two_level_approval(): void
    {
        Notification::fake();
        app(SettingService::class)->set(['leave_approval_levels' => 2]);
        $leave = $this->leaveFor($this->makeEmployee());

        Notification::assertSentTo($this->manager, SystemNotification::class);
        Notification::assertNotSentTo($this->admin, SystemNotification::class);

        $this->actingAs($this->manager)->post(route('admin.leaves.approve', $leave))->assertForbidden();
        $this->actingAs($this->manager)->post(route('admin.leaves.recommend', $leave))->assertSessionHas('success');
        $this->assertSame('recommended', $leave->fresh()->status);
        $this->assertSame($this->manager->id, $leave->fresh()->recommended_by);
        Notification::assertSentTo($this->admin, SystemNotification::class, fn ($n) => $n->title === 'Leave recommended');

        $this->actingAs($this->admin)->get(route('admin.leaves.index', ['status' => 'recommended']))->assertSee('Approve');
        $this->actingAs($this->admin)->post(route('admin.leaves.approve', $leave))->assertSessionHas('success');
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_admin_can_create_manager_and_employee_can_be_made_manager(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'CTG Manager', 'email' => 'ctg@example.com', 'role' => 'manager', 'branch_id' => $this->other->id, 'password' => 'password1', 'is_active' => 1,
        ])->assertRedirect();
        $this->assertSame($this->other->id, User::where('email', 'ctg@example.com')->value('branch_id'));

        $employee = $this->makeEmployee(['email' => 'lead@example.com']);
        $this->actingAs($this->admin)->put(route('admin.employees.update', $employee), $employee->only([
            'employee_code', 'name', 'email', 'branch_id',
        ]) + ['employment_type' => 'permanent', 'status' => 'active', 'joining_date' => '2025-01-01', 'create_login' => 1, 'login_role' => 'manager', 'password' => 'password1'])->assertRedirect();

        $user = $employee->fresh()->user;
        $this->assertTrue($user->isManager());
        $this->assertSame($employee->branch_id, $user->branch_id);
    }
}
