<?php

use App\Services\SettingService;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, bool $symbol = true): string
    {
        $formatted = number_format((float) $amount, 2);

        return $symbol ? setting('currency_symbol', '৳').' '.$formatted : $formatted;
    }
}

if (! function_exists('minutes_to_hours')) {
    function minutes_to_hours(?int $minutes): string
    {
        $minutes = (int) $minutes;

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
