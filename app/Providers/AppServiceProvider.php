<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Services\Device\DeviceClient;
use App\Services\Device\ZktecoUdpClient;
use App\Services\SettingService;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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

        Event::listen(Login::class, fn (Login $event) => AuditLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'event' => 'login',
            'auditable_type' => get_class($event->user),
            'auditable_id' => $event->user->getAuthIdentifier(),
            'description' => $event->user->name,
            'ip_address' => request()->ip(),
        ]));

        try {
            $this->app->make(SettingService::class)->applyTimezone();
        } catch (Throwable) {
        }
    }
}
