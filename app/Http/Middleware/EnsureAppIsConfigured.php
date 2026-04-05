<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SystemSetting::boolean('is_app_configured', false)) {
            return $next($request);
        }

        if ($request->routeIs('setup.*') || $request->is('setup')) {
            return $next($request);
        }

        return redirect()->route('setup.index');
    }
}
