<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request)
    {
        Password::sendResetLink(['email' => $request->validated('email'), 'is_active' => true]);

        return back()->with('success', 'If this email belongs to an active account, a reset link has been sent.');
    }

    public function edit(string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function update(ResetPasswordRequest $request)
    {
        $status = Password::reset($request->validated(), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Your password has been reset. Please log in.')
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
