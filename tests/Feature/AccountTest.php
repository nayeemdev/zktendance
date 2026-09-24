<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCompany();
    }

    public function test_employee_updates_own_phone_and_address(): void
    {
        $employee = $this->makeEmployee(['name' => 'Rafi']);
        $user = User::create(['name' => 'Rafi', 'email' => 'rafi@example.com', 'password' => 'password', 'role' => 'employee']);
        $employee->update(['user_id' => $user->id]);

        $this->actingAs($user)->put(route('profile.update'), ['phone' => '01711000000', 'address' => 'Mirpur, Dhaka', 'name' => 'Hacked'])->assertSessionHas('success');

        $this->assertSame('01711000000', $employee->fresh()->phone);
        $this->assertSame('Mirpur, Dhaka', $employee->fresh()->address);
        $this->assertSame('Rafi', $employee->fresh()->name);
    }

    public function test_staff_updates_name_and_password(): void
    {
        $this->actingAs($this->admin)->put(route('profile.update'), ['name' => 'Chief Admin'])->assertSessionHas('success');
        $this->assertSame('Chief Admin', $this->admin->fresh()->name);

        $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'newsecret1', 'password_confirmation' => 'newsecret1',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('newsecret1', $this->admin->fresh()->password));
    }

    public function test_forgot_and_reset_password(): void
    {
        Notification::fake();

        $this->get(route('password.request'))->assertOk();
        $this->get(route('login'))->assertSee('Forgot your password?');
        $this->post(route('password.email'), ['email' => 'admin@example.com'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($this->admin, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => 'admin@example.com']))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'admin@example.com', 'password' => 'brandnew1', 'password_confirmation' => 'brandnew1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brandnew1', $this->admin->fresh()->password));
    }

    public function test_inactive_user_gets_no_reset_link(): void
    {
        Notification::fake();
        $user = User::create(['name' => 'Old', 'email' => 'old@example.com', 'password' => 'password', 'role' => 'hr', 'is_active' => false]);

        $this->post(route('password.email'), ['email' => 'old@example.com'])->assertSessionHas('success');
        Notification::assertNotSentTo($user, ResetPassword::class);
    }
}
