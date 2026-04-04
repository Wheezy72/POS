<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMpesaPayment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PaymentApiController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $paymentGatewayService)
    {
    }

    public function receiveC2bWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        $validated = validator($payload, [
            'TransID' => ['required', 'string', 'max:255'],
            'TransAmount' => ['required', 'numeric', 'gt:0'],
            'BillRefNumber' => ['nullable', 'string', 'max:255'],
            'MSISDN' => ['nullable', 'string', 'max:255'],
            'FirstName' => ['nullable', 'string', 'max:255'],
            'MiddleName' => ['nullable', 'string', 'max:255'],
            'LastName' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $customerName = trim(implode(' ', array_filter([
            $validated['FirstName'] ?? null,
            $validated['MiddleName'] ?? null,
            $validated['LastName'] ?? null,
        ])));

        if ($customerName === '') {
            $customerName = 'Unknown Customer';
        }

        $payment = IncomingMpesaPayment::query()->firstOrCreate(
            ['transaction_code' => $validated['TransID']],
            [
                'customer_name' => $customerName,
                'phone_number' => $validated['MSISDN'] ?? '',
                'amount' => round((float) $validated['TransAmount'], 2),
                'status' => 'pending',
                'claimed_at' => null,
            ]
        );

        Log::info('Incoming M-PESA C2B payment captured.', [
            'transaction_code' => $payment->transaction_code,
            'bill_ref_number' => $validated['BillRefNumber'] ?? null,
            'amount' => $payment->amount,
        ]);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    public function liveFeed(): JsonResponse
    {
        $payments = IncomingMpesaPayment::query()
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'incoming_payments' => $payments,
        ]);
    }

    public function stkPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['required', 'string', 'max:255'],
        ]);

        try {
            $checkoutRequestId = $this->paymentGatewayService->initiateStkPush(
                $validated['phone'],
                (float) $validated['amount'],
                $validated['reference']
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to initiate STK push.',
            ], 500);
        }

        return response()->json([
            'message' => 'STK push initiated successfully.',
            'checkout_request_id' => $checkoutRequestId,
        ], 202);
    }

    public function stkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkout_request_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            $status = $this->paymentGatewayService->checkStkStatus($validated['checkout_request_id']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to check STK status.',
            ], 500);
        }

        return response()->json([
            'message' => 'STK status retrieved successfully.',
            'status' => $status,
        ]);
    }
}
