<?php

namespace App\Providers;

use App\Services\Device\DeviceClient;
use App\Services\Device\ZktecoUdpClient;
use App\Services\SettingService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
        $this->app->bind(DeviceClient::class, ZktecoUdpClient::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        try {
            $timezone = $this->app->make(SettingService::class)->get('timezone');
            if ($timezone) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (Throwable) {
        }
    }
}
