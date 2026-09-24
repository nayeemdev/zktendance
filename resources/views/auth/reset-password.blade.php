@extends('layouts.guest')

@section('title', 'Choose a New Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <h1 class="h4 text-center mb-4">Choose a new password</h1>
        @include('layouts.partials.alerts')
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <x-input name="email" type="email" label="Email" :value="$email" required />
                    <x-input name="password" type="password" label="New Password" required />
                    <x-input name="password_confirmation" type="password" label="Confirm Password" required />
                    <button class="btn btn-primary w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
