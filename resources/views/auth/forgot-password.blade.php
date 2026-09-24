@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <h1 class="h4 text-center mb-4">Reset your password</h1>
        @include('layouts.partials.alerts')
        <div class="card">
            <div class="card-body p-4">
                <p class="small text-muted">Enter your login email and we will send you a link to choose a new password.</p>
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <x-input name="email" type="email" label="Email" required autofocus />
                    <button class="btn btn-primary w-100">Send Reset Link</button>
                </form>
                <div class="text-center mt-3"><a href="{{ route('login') }}" class="small">Back to login</a></div>
            </div>
        </div>
    </div>
</div>
@endsection
