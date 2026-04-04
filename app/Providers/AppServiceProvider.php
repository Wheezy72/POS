<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('pin-login', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by((string) $request->ip())
                ->response(function (): \Illuminate\Http\JsonResponse {
                    return response()->json([
                        'message' => 'Too many PIN login attempts. Please wait one minute before trying again.',
                    ], 429);
                });
        });

        RateLimiter::for('manager-override', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(5)
                ->by((string) $key)
                ->response(function (): \Illuminate\Http\JsonResponse {
                    return response()->json([
                        'message' => 'Too many manager override attempts. Please wait one minute before trying again.',
                    ], 429);
                });
        });
    }
}
