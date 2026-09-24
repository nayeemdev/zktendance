@extends('layouts.guest')

@section('title', 'Choose a New Password')

@section('auth')
<span class="stat-icon tone-indigo mb-3"><i class="hgi-stroke hgi-lock-password"></i></span>
<h1 class="h3 mb-1">Choose a new password</h1>
<p class="text-muted mb-4">Use at least 8 characters.</p>
@include('layouts.partials.alerts')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-input name="email" type="email" label="Email" :value="$email" required />
    <x-input name="password" type="password" label="New Password" required />
    <x-input name="password_confirmation" type="password" label="Confirm Password" required />
    <button class="btn btn-primary btn-lg w-100">Reset Password</button>
</form>
@endsection
