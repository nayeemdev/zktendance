<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile', ['user' => auth()->user()->load('employee')]);
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if ($user->employee) {
            $user->employee->update(['phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null]);
        } else {
            $user->update(['name' => $data['name']]);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function password(PasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Password changed.');
    }
}
