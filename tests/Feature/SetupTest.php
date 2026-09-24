<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\TaxSlab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_redirects_to_setup(): void
    {
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertSee('Complete Setup');
    }

    public function test_setup_saves_settings_and_creates_admin(): void
    {
        $this->post('/setup', [
            'company_name' => 'Acme Ltd',
            'country' => 'BD',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'timezone' => 'Asia/Dhaka',
            'branch_name' => 'Head Office',
            'office_start' => '09:00',
            'office_end' => '18:00',
            'admin_name' => 'Owner',
            'admin_email' => 'owner@example.com',
            'admin_password' => 'secret123',
            'admin_password_confirmation' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
        $this->assertSame('BDT', setting('currency'));
        $this->assertSame('Asia/Dhaka', setting('timezone'));
        $this->assertTrue(User::first()->isAdmin());
        $this->assertSame([5], Branch::first()->weekend_days);
        $this->assertTrue(Shift::first()->is_default);
        $this->assertSame(6, TaxSlab::count());
        $this->assertTrue(LeaveType::where('code', 'CL')->exists());

        $this->get('/setup')->assertRedirect('/login');
    }

    public function test_employee_cannot_open_admin_pages(): void
    {
        $this->setUpCompany();
        $employee = $this->makeEmployee();
        $user = User::create(['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'password', 'role' => 'employee']);
        $employee->update(['user_id' => $user->id]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/')->assertRedirect(route('portal.dashboard'));
        $this->actingAs($user)->get('/portal')->assertOk();
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->setUpCompany();

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_timezone_is_applied_right_after_setup(): void
    {
        $original = date_default_timezone_get();

        $this->post('/setup', [
            'company_name' => 'Acme Ltd', 'country' => 'BD', 'currency' => 'BDT', 'currency_symbol' => '৳',
            'timezone' => 'Asia/Dhaka', 'branch_name' => 'Head Office', 'office_start' => '09:00', 'office_end' => '18:00',
            'admin_name' => 'Owner', 'admin_email' => 'owner@example.com', 'admin_password' => 'secret123', 'admin_password_confirmation' => 'secret123',
        ]);

        $this->assertSame('Asia/Dhaka', date_default_timezone_get());
        $this->assertSame('Asia/Dhaka', config('app.timezone'));

        date_default_timezone_set($original);
        config(['app.timezone' => $original]);
    }
}
