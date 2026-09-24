<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\AttendanceLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    private Shift $evening;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 12:00:00');
        $this->setUpCompany();
        $this->evening = Shift::create(['name' => 'Evening', 'start_time' => '14:00', 'end_time' => '22:00', 'grace_minutes' => 10, 'half_day_minutes' => 240, 'break_minutes' => 0]);
    }

    public function test_roster_shift_is_used_for_attendance(): void
    {
        $employee = $this->makeEmployee();
        app(AttendanceLogService::class)->store(null, [
            ['user_id' => $employee->device_user_id, 'timestamp' => '2026-09-14 14:05:00'],
            ['user_id' => $employee->device_user_id, 'timestamp' => '2026-09-14 22:00:00'],
        ]);
        $this->assertSame(Attendance::LATE, Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-14')->value('status'));

        $this->actingAs($this->admin)->post(route('admin.roster.store'), [
            'employee_ids' => [$employee->id],
            'shift_ids' => [$this->evening->id],
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-20',
        ])->assertRedirect();

        $day = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-14')->first();
        $this->assertSame(Attendance::PRESENT, $day->status);
        $this->assertSame($this->evening->id, $day->shift_id);
        $this->assertSame($this->evening->id, $employee->shiftOn(Carbon::parse('2026-09-15'))->id);
        $this->assertSame(Shift::first()->id, $employee->shiftOn(Carbon::parse('2026-09-21'))->id);
    }

    public function test_rotation_creates_alternating_blocks_for_branch(): void
    {
        $this->makeEmployee();
        $this->makeEmployee();
        $general = Shift::first();

        $this->actingAs($this->admin)->post(route('admin.roster.store'), [
            'branch_id' => Branch::first()->id,
            'shift_ids' => [$general->id, $this->evening->id, ''],
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-28',
            'rotate_days' => 7,
        ])->assertSessionHasNoErrors();

        $this->assertSame(8, ShiftAssignment::count());
        $employee = ShiftAssignment::first()->employee;
        $this->assertSame($general->id, $employee->shiftOn(Carbon::parse('2026-10-03'))->id);
        $this->assertSame($this->evening->id, $employee->shiftOn(Carbon::parse('2026-10-09'))->id);
        $this->assertSame($general->id, $employee->shiftOn(Carbon::parse('2026-10-16'))->id);
    }

    public function test_requires_a_target_and_page_loads(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roster.store'), [
            'shift_ids' => [$this->evening->id],
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-07',
        ])->assertSessionHasErrors('employee_ids');

        $employee = $this->makeEmployee();
        ShiftAssignment::create(['employee_id' => $employee->id, 'shift_id' => $this->evening->id, 'start_date' => '2026-09-28', 'end_date' => '2026-10-10']);

        $this->actingAs($this->admin)->get(route('admin.roster.index'))->assertOk()->assertSee('Evening');
        $this->actingAs($this->admin)->get(route('admin.roster.create'))->assertOk();

        $this->actingAs($this->admin)->delete(route('admin.roster.destroy', ShiftAssignment::first()))->assertRedirect();
        $this->assertSame(0, ShiftAssignment::count());
    }
}
