<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->employee, 403, 'Your account is not linked to an employee profile.');

        return $next($request);
    }
}
