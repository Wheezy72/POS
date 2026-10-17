<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
        $serverDate = now();
        $warnThreshold = max(
            1,
            SystemSetting::integer('pos_clock_warn_drift_seconds', (int) config('pos.clock.warn_drift_seconds', 30))
        );
        $rejectThreshold = max(
            $warnThreshold + 1,
            SystemSetting::integer('pos_clock_reject_drift_seconds', (int) config('pos.clock.reject_drift_seconds', 300))
        );
        $drift = null;

        if ($clientTime) {
            try {
                $clientDate = Carbon::parse($clientTime);
                $drift = abs($serverDate->diffInSeconds($clientDate, false));

                if ($drift > $warnThreshold) {
                    Log::warning('POS Terminal Clock Drift Detected', [
                        'client_time' => $clientTime,
                        'server_time' => $serverDate->toIso8601String(),
                        'drift_seconds' => $drift,
                        'warn_threshold_seconds' => $warnThreshold,
                        'reject_threshold_seconds' => $rejectThreshold,
                        'ip' => $request->ip(),
                    ]);
                }

                if ($drift > $rejectThreshold) {
                    return response()->json([
                        'error' => 'CLOCK_DRIFT',
                        'message' => 'Terminal clock drift exceeded safe tolerance.',
                        'server_time' => $serverDate->toIso8601String(),
                        'drift_seconds' => $drift,
                        'reject_threshold_seconds' => $rejectThreshold,
                    ], 403)->withHeaders([
                        'X-Server-Time' => $serverDate->toIso8601String(),
                        'X-Terminal-Drift-Seconds' => (string) $drift,
                    ]);
                }
            } catch (\Exception) {
                Log::warning('Malformed X-Terminal-Time header', ['value' => $clientTime]);
            }
        }

        $response = $next($request);
        $response->headers->set('X-Server-Time', $serverDate->toIso8601String());

        if ($drift !== null) {
            $response->headers->set('X-Terminal-Drift-Seconds', (string) $drift);
        }

        return $response;
    }
}
