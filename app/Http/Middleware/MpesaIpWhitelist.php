<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MpesaIpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Safaricom M-Pesa Production C2B ASN/IP Blocks (Example production blocks)
        $allowedIps = [
            '196.201.214.200/32',
            '196.201.214.206/32',
            '196.201.213.114/32',
            '196.201.214.207/32',
            '196.201.214.208/32',
            '196.201.213.44/32',
            '196.201.212.127/32',
            '196.201.212.138/32',
            '196.201.212.129/32',
            '196.201.212.132/32',
            '196.201.212.136/32',
            '196.201.212.128/32',
            '196.202.188.0/22', // Widely documented Safaricom range
            '41.203.208.0/20',
        ];

        if (!$request->isIpConfinedTo($allowedIps)) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized M-Pesa Webhook Attempt', [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Rejected'], 403);
        }

        return $next($request);
    }
}
