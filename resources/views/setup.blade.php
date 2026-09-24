@extends('layouts.guest')

@section('title', 'First Time Setup')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="hero mb-4">
            <div class="position-relative d-flex align-items-center gap-3" style="z-index: 1">
                <span class="brand-mark" style="width: 52px; height: 52px; font-size: 1.6rem"><i class="hgi-stroke hgi-fingerprint-scan"></i></span>
                <div>
                    <h1 class="h3 mb-1">Welcome! Let's set up your office</h1>
                    <div class="opacity-75">Four quick steps. Everything can be changed later in Settings.</div>
                </div>
            </div>
        </div>

        @include('layouts.partials.alerts')

        <form method="POST" action="{{ route('setup.store') }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">1. Company</div>
                <div class="card-body row">
                    <x-input name="company_name" label="Company Name" class="col-md-6 mb-3" required />
                    <x-input name="company_email" type="email" label="Company Email" class="col-md-6 mb-3" />
                    <x-input name="company_phone" label="Phone" class="col-md-6 mb-3" />
                    <x-input name="company_address" label="Address" class="col-md-6 mb-3" />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">2. Country, Currency &amp; Timezone</div>
                <div class="card-body row">
                    <x-select name="country" label="Country" :options="collect($countries)->map(fn ($c) => $c['name'])" value="BD" class="col-md-6 mb-3" required />
                    <x-select name="timezone" label="Timezone" :options="array_combine($timezones, $timezones)" value="Asia/Dhaka" class="col-md-6 mb-3" required />
                    <x-input name="currency" label="Currency Code" value="BDT" class="col-md-6 mb-3" required />
                    <x-input name="currency_symbol" label="Currency Symbol" value="৳" class="col-md-6 mb-3" required />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">3. Main Branch &amp; Office Hours</div>
                <div class="card-body row">
                    <x-input name="branch_name" label="Branch Name" value="Head Office" class="col-md-4 mb-3" required />
                    <x-input name="office_start" type="time" label="Office Starts" value="09:00" class="col-md-4 mb-3" required />
                    <x-input name="office_end" type="time" label="Office Ends" value="18:00" class="col-md-4 mb-3" required />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">4. Admin Account</div>
                <div class="card-body row">
                    <x-input name="admin_name" label="Your Name" class="col-md-6 mb-3" required />
                    <x-input name="admin_email" type="email" label="Login Email" class="col-md-6 mb-3" required />
                    <x-input name="admin_password" type="password" label="Password" class="col-md-6 mb-3" required />
                    <x-input name="admin_password_confirmation" type="password" label="Confirm Password" class="col-md-6 mb-3" required />
                </div>
            </div>

            <button class="btn btn-primary btn-lg w-100">Complete Setup</button>
        </form>
    </div>
</div>

<script>
    const countries = @json($countries);
    document.getElementById('country').addEventListener('change', function () {
        const c = countries[this.value];
        document.getElementById('currency').value = c.currency;
        document.getElementById('currency_symbol').value = c.symbol;
        document.getElementById('timezone').value = c.timezone;
    });
</script>
@endsection
