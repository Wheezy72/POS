<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentGatewayService
{
    public function getDarajaToken(): string
    {
        $config = config('payments.daraja');

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withBasicAuth((string) $config['consumer_key'], (string) $config['consumer_secret'])
                ->acceptJson()
                ->timeout((int) $config['timeout_seconds'])
                ->get('/oauth/v1/generate', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                Log::error('Daraja token request failed.', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new RuntimeException('Unable to obtain Daraja access token.');
            }

            $accessToken = $response->json('access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Daraja access token response was empty.');
            }

            return $accessToken;
        } catch (Throwable $exception) {
            Log::error('Daraja token request threw an exception.', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to obtain Daraja access token.', 0, $exception);
        }
    }

    public function initiateStkPush(string $phone, float $amount, string $reference): string
    {
        $config = config('payments.daraja');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode((string) $config['shortcode'] . (string) $config['passkey'] . $timestamp);

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withToken($this->getDarajaToken())
                ->acceptJson()
                ->timeout((int) $config['timeout_seconds'])
                ->post('/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => (string) $config['shortcode'],
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => round($amount, 2),
                    'PartyA' => $phone,
                    'PartyB' => (string) $config['shortcode'],
                    'PhoneNumber' => $phone,
                    'CallBackURL' => (string) $config['stk_callback_url'],
                    'AccountReference' => $reference,
                    'TransactionDesc' => 'Duka-App POS Payment',
                ]);

            if (! $response->successful()) {
                Log::error('Daraja STK push failed.', [
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new RuntimeException('Unable to initiate STK push.');
            }

            $checkoutRequestId = $response->json('CheckoutRequestID');

            if (! is_string($checkoutRequestId) || $checkoutRequestId === '') {
                throw new RuntimeException('Daraja STK push did not return a CheckoutRequestID.');
            }

            Log::info('Daraja STK push initiated.', [
                'phone' => $phone,
                'amount' => round($amount, 2),
                'reference' => $reference,
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return $checkoutRequestId;
        } catch (Throwable $exception) {
            Log::error('Daraja STK push threw an exception.', [
                'phone' => $phone,
                'amount' => $amount,
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to initiate STK push.', 0, $exception);
        }
    }

    public function checkStkStatus(string $checkoutRequestId): array
    {
        $config = config('payments.daraja');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode((string) $config['shortcode'] . (string) $config['passkey'] . $timestamp);

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withToken($this->getDarajaToken())
                ->acceptJson()
                ->timeout((int) $config['timeout_seconds'])
                ->post('/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => (string) $config['shortcode'],
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            if (! $response->successful()) {
                Log::error('Daraja STK status query failed.', [
                    'checkout_request_id' => $checkoutRequestId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new RuntimeException('Unable to query STK push status.');
            }

            $payload = $response->json();

            Log::info('Daraja STK status queried.', [
                'checkout_request_id' => $checkoutRequestId,
                'response' => $payload,
            ]);

            return is_array($payload) ? $payload : [];
        } catch (Throwable $exception) {
            Log::error('Daraja STK status query threw an exception.', [
                'checkout_request_id' => $checkoutRequestId,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to query STK push status.', 0, $exception);
        }
    }

    public function initiatePesapal(array $payload = []): array
    {
        Log::info('Pesapal initiation stub invoked.', [
            'reference' => $payload['reference'] ?? null,
            'payload' => $payload,
        ]);

        return [
            'status' => 'mock_initiated',
            'tracking_id' => 'PESAPAL-' . Str::upper(Str::random(10)),
        ];
    }

    public function checkPesapalStatus(string $trackingId): array
    {
        Log::info('Pesapal status stub invoked.', [
            'tracking_id' => $trackingId,
        ]);

        return [
            'status' => 'mock_pending',
            'tracking_id' => $trackingId,
        ];
    }
}
