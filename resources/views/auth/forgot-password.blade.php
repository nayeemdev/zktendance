@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('auth')
<span class="stat-icon tone-indigo mb-3"><i class="hgi-stroke hgi-lock-password"></i></span>
<h1 class="h3 mb-1">Reset your password</h1>
<p class="text-muted mb-4">Enter your login email and we will send you a link to choose a new password.</p>
@include('layouts.partials.alerts')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <x-input name="email" type="email" label="Email" placeholder="you@company.com" required autofocus />
    <button class="btn btn-primary btn-lg w-100">Send Reset Link</button>
</form>
<div class="text-center mt-4"><a href="{{ route('login') }}" class="small"><i class="hgi-stroke hgi-arrow-left-01"></i> Back to login</a></div>
@endsection
