<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'app_settings';

    public const DEFAULTS = [
        'company_name' => 'My Company',
        'company_address' => '',
        'company_phone' => '',
        'company_email' => '',
        'company_logo' => null,
        'country' => 'BD',
        'currency' => 'BDT',
        'currency_symbol' => '৳',
        'timezone' => 'Asia/Dhaka',
        'fiscal_year_start_month' => 7,
        'salary_day_basis' => 'calendar',
        'absent_deduction_base' => 'gross',
        'late_days_per_deduction' => 3,
        'tax_enabled' => true,
        'tax_exempt_fraction' => 0.3333,
        'tax_exempt_cap' => 500000,
        'minimum_tax' => 5000,
        'payslip_footer' => 'This is a system generated payslip.',
        'email_payslips' => false,
        'setup_completed' => false,
    ];

    private ?array $values = null;

    public function all(): array
    {
        if ($this->values === null) {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->toArray());
            $this->values = array_merge(self::DEFAULTS, array_map(fn ($v) => json_decode($v, true), $stored));
        }

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function isSetupCompleted(): bool
    {
        return (bool) $this->get('setup_completed');
    }
}
