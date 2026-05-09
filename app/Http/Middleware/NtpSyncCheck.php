<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NtpSyncCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientTime = $request->header('X-Terminal-Time'); // Expect ISO8601 or Timestamp

        if (!$clientTime) {
            return $next($request);
        }

        try {
            $clientDate = \Illuminate\Support\Carbon::parse($clientTime);
            $serverDate = now();

            $drift = abs($serverDate->diffInSeconds($clientDate, false));

            if ($drift > 5) {
                \Illuminate\Support\Facades\Log::warning('POS Terminal Clock Drift Detected', [
                    'client_time' => $clientTime,
                    'server_time' => $serverDate->toIso8601String(),
                    'drift_seconds' => $drift,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'CLOCK_DRIFT',
                    'message' => 'Terminal clock is out of sync with the server. Please synchronize your system time.',
                    'server_time' => $serverDate->toIso8601String(),
                ], 403);
            }
        } catch (\Exception $e) {
            // If parsing fails, we might want to log it but maybe not block if it's just a malformed header
            \Illuminate\Support\Facades\Log::error('Malformed X-Terminal-Time header', ['value' => $clientTime]);
        }

        return $next($request);
    }
}
