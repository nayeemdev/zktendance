@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <i class="bi bi-fingerprint display-4 text-primary"></i>
            <h1 class="h4 mt-2">{{ setting('company_name') }}</h1>
            <p class="text-muted">Attendance &amp; Payroll</p>
        </div>
        @include('layouts.partials.alerts')
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <x-input name="email" type="email" label="Email" required autofocus />
                    <x-input name="password" type="password" label="Password" required />
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button class="btn btn-primary w-100">Login</button>
                    <div class="text-center mt-3"><a href="{{ route('password.request') }}" class="small">Forgot your password?</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
