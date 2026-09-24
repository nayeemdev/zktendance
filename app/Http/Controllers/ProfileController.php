<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile', ['user' => auth()->user()->load('employee')]);
    }

    public function update(PasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Password changed.');
    }
}
