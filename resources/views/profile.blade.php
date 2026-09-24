@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Account</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $user->name }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                    <dt class="col-sm-4">Role</dt><dd class="col-sm-8">{{ \App\Models\User::ROLES[$user->role] }}</dd>
                    @if ($user->employee)
                        <dt class="col-sm-4">Employee ID</dt><dd class="col-sm-8">{{ $user->employee->employee_code }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-white fw-semibold">Edit Profile</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    @if ($user->employee)
                        <x-input name="phone" label="Phone" :value="$user->employee->phone" />
                        <x-textarea name="address" label="Address" :value="$user->employee->address" rows="2" />
                        <p class="small text-muted">Ask HR to change your name, email, bank or job details.</p>
                    @else
                        <x-input name="name" label="Name" :value="$user->name" required />
                    @endif
                    <button class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Change Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    <x-input name="current_password" type="password" label="Current Password" required />
                    <x-input name="password" type="password" label="New Password" required />
                    <x-input name="password_confirmation" type="password" label="Confirm New Password" required />
                    <button class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
