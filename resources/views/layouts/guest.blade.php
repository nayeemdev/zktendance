<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ setting('company_name') }}</title>
    <script>
        try {
            var theme = localStorage.getItem('zk-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=2" rel="stylesheet">
</head>
<body>
@hasSection('auth')
    <div class="auth-wrap">
        <div class="auth-brand">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-mark"><i class="hgi-stroke hgi-fingerprint-scan"></i></span>
                <span>
                    <span class="d-block fw-semibold fs-5">{{ setting('company_name') }}</span>
                    <span class="small opacity-75">Attendance &amp; Payroll</span>
                </span>
            </div>
            <div style="max-width: 440px">
                <h2 class="display-6 fw-bold mb-4" style="letter-spacing: -.03em">Every punch, leave and payslip in one place.</h2>
                <div class="feature"><i class="hgi-stroke hgi-fingerprint-scan"></i><div><div class="fw-semibold">ZKTeco devices</div><div class="small opacity-75">Punches arrive by push or pull and turn into attendance automatically.</div></div></div>
                <div class="feature"><i class="hgi-stroke hgi-calendar-check-in-01"></i><div><div class="fw-semibold">Leave and shifts</div><div class="small opacity-75">Rosters, approvals and balances that stay in sync with attendance.</div></div></div>
                <div class="feature"><i class="hgi-stroke hgi-money-bag-02"></i><div><div class="fw-semibold">Payroll and payslips</div><div class="small opacity-75">Overtime, tax and deductions calculated from real attendance.</div></div></div>
            </div>
            <div class="small opacity-50">&copy; {{ now()->year }} {{ setting('company_name') }}</div>
        </div>
        <div class="auth-form">
            <div class="inner">
                @yield('auth')
            </div>
        </div>
    </div>
@else
    <div class="guest-wide">
        @yield('content')
    </div>
@endif
</body>
</html>
