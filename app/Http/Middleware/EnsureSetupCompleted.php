<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupCompleted
{
    public function __construct(private SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $completed = $this->settings->isSetupCompleted();

        if (! $completed && ! $request->routeIs('setup*')) {
            return redirect()->route('setup');
        }

        if ($completed && $request->routeIs('setup*')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
