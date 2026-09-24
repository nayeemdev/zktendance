@extends('layouts.guest')

@section('title', 'Login')

@section('auth')
<div class="d-lg-none d-flex align-items-center gap-2 mb-4">
    <span class="brand-mark"><i class="hgi-stroke hgi-fingerprint-scan"></i></span>
    <span class="fw-semibold">{{ setting('company_name') }}</span>
</div>
<h1 class="h3 mb-1">Welcome back</h1>
<p class="text-muted mb-4">Sign in to continue to your workspace.</p>
@include('layouts.partials.alerts')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <x-input name="email" type="email" label="Email" placeholder="you@company.com" required autofocus />
    <x-input name="password" type="password" label="Password" placeholder="Your password" required />
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">Remember me</label>
        </div>
        <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
    </div>
    <button class="btn btn-primary btn-lg w-100">Login</button>
</form>
@endsection
