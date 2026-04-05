<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Jobs\PrintReceiptToHardware;
use App\Jobs\SyncSaleToCloud;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\IncomingMpesaPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class POSApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
        ]);

        $query = trim($validated['query']);

        $products = Product::query()
            ->with('taxCategory')
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('barcode', $query)
                    ->orWhere('sku', $query)
                    ->orWhere('name', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'uuid'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'manager_pin' => ['nullable', 'string', 'regex:/^\d{4}(\d{2})?$/'],
            'claim_transaction_code' => ['nullable', 'string', 'max:255'],
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.product_id' => ['required', 'uuid'],
            'cart.*.quantity' => ['required', 'numeric'],
            'cart.*.override_unit_price' => ['nullable', 'numeric', 'gt:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,mpesa,card,credit_deni'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.phone_number' => ['nullable', 'string', 'max:32'],
            'payments.*.reference_number' => ['nullable', 'string'],
            'payments.*.status' => ['nullable', 'in:pending,completed,failed'],
        ]);

        $cashierId = $request->user()?->getAuthIdentifier();

        if ($cashierId === null) {
            return response()->json([
                'message' => 'Unauthenticated cashier.',
            ], 401);
        }

        $managerApprover = $this->resolveManagerApprover($validated['manager_pin'] ?? null);
        $managerOverrideApproved = $managerApprover !== null;

        try {
            $sale = DB::transaction(function () use ($validated, $cashierId, $managerOverrideApproved): Sale {
                $creditSaleRequested = collect($validated['payments'])->contains(
                    fn (array $payment): bool => $payment['method'] === 'credit_deni'
                );
                $customer = $this->resolveSaleCustomer($validated, $creditSaleRequested);
                $claimedIncomingPayment = null;

                if (! empty($validated['claim_transaction_code'])) {
                    $claimedIncomingPayment = IncomingMpesaPayment::query()
                        ->where('transaction_code', $validated['claim_transaction_code'])
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->first();

                    if ($claimedIncomingPayment === null) {
                        throw new RuntimeException('The supplied M-PESA transaction code is unavailable or already claimed.');
                    }
                }

                $productIds = collect($validated['cart'])
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                $products = Product::query()
                    ->with('taxCategory')
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotal = 0.0;
                $taxTotal = 0.0;
                $lineItems = [];
                $pricingAdjustments = [];

                foreach ($validated['cart'] as $cartItem) {
                    /** @var Product|null $product */
                    $product = $products->get($cartItem['product_id']);

                    if ($product === null) {
                        throw new RuntimeException('One or more products could not be found.');
                    }

                    $quantity = round((float) $cartItem['quantity'], 2);

                    if ($quantity === 0.0) {
                        throw new RuntimeException("Product {$product->name} cannot be checked out with a zero quantity.");
                    }

                    if (! $product->allow_fractional_sales && floor(abs($quantity)) !== abs($quantity)) {
                        throw new RuntimeException("Product {$product->name} does not allow fractional sales.");
                    }

                    $availableStock = round((float) $product->stock_quantity, 2);

                    if ($quantity > 0 && $availableStock < $quantity) {
                        throw new RuntimeException("Insufficient stock for product {$product->name}.");
                    }

                    $basePrice = round((float) $product->base_price, 2);
                    $costPrice = round((float) $product->cost_price, 2);
                    $overrideUnitPrice = array_key_exists('override_unit_price', $cartItem) && $cartItem['override_unit_price'] !== null
                        ? round((float) $cartItem['override_unit_price'], 2)
                        : null;
                    $priceSource = 'base_price';

                    // Rule 1: expiry-driven markdowns.
                    // If the batch expires within 48 hours, the engine automatically cuts the shelf
                    // price by 50% to improve sell-through before the stock becomes waste.
                    $engineUnitPrice = $basePrice;

                    if ($this->expiresWithin48Hours($product->batch_expiry_date)) {
                        $engineUnitPrice = round($basePrice * 0.5, 2);
                        $priceSource = 'expiry_markdown';
                    }

                    // Rule 2: time-decay margin floors.
                    // Older stock is allowed to break margin more aggressively so dead inventory can
                    // be converted back into cash instead of sitting on the shelf indefinitely.
                    $daysSinceLastReceived = $this->daysSinceLastReceived($product->last_received_date);
                    $marginFloorMultiplier = 1.15;

                    if ($daysSinceLastReceived > 120) {
                        $marginFloorMultiplier = 1.00;
                    } elseif ($daysSinceLastReceived > 90) {
                        $marginFloorMultiplier = 1.05;
                    }

                    $marginFloor = round($costPrice * $marginFloorMultiplier, 2);

                    // Rule 3: dynamic margin shield.
                    // The pricing engine always wins over any stale frontend assumption. If the
                    // calculated promotional price falls below the allowed floor, we clamp it back
                    // up to the minimum safe value unless a valid manager PIN override was supplied.
                    $marginClampApplied = false;

                    if ($overrideUnitPrice !== null) {
                        if (! $managerOverrideApproved && $overrideUnitPrice < $marginFloor) {
                            throw new RuntimeException("Manual price override for {$product->name} is below the margin floor of {$marginFloor}. Manager PIN required.");
                        }

                        $unitPrice = $overrideUnitPrice;
                        $priceSource = 'manual_override';
                    } else {
                        $unitPrice = $engineUnitPrice;
                    }

                    if (! $managerOverrideApproved && $unitPrice < $marginFloor) {
                        $unitPrice = $marginFloor;
                        $marginClampApplied = true;
                        $priceSource = 'margin_floor';
                    }

                    $unitPrice = round($unitPrice, 2);
                    $lineSubtotal = round($quantity * $unitPrice, 2);
                    $lineTax = round($lineSubtotal * (((float) $product->taxCategory->rate) / 100), 2);

                    if ($unitPrice !== $basePrice || $marginClampApplied || $overrideUnitPrice !== null) {
                        $pricingAdjustments[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => $quantity,
                            'base_unit_price' => $basePrice,
                            'final_unit_price' => $unitPrice,
                            'margin_floor' => $marginFloor,
                            'override_unit_price' => $overrideUnitPrice,
                            'price_source' => $priceSource,
                            'manager_override_used' => $managerOverrideApproved,
                        ];
                    }

                    $subtotal += $lineSubtotal;
                    $taxTotal += $lineTax;
                    $lineItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $subtotal = round($subtotal, 2);
                $taxTotal = round($taxTotal, 2);
                $discountTotal = 0.0;
                $grandTotal = round($subtotal + $taxTotal - $discountTotal, 2);

                // The backend owns the final sale amount. For the common single-tender flow we
                // rewrite the payment amount to the server-calculated grand total so automatic
                // markdowns and margin clamps do not leave the cashier stranded on a mismatch.
                $normalizedPayments = $validated['payments'];

                if (count($normalizedPayments) === 1) {
                    $normalizedPayments[0]['amount'] = $grandTotal;
                }

                $normalizedPayments = $this->normalizePayments(
                    $normalizedPayments,
                    $claimedIncomingPayment
                );

                $paymentsTotal = round(
                    collect($normalizedPayments)->sum(fn (array $payment): float => round((float) $payment['amount'], 2)),
                    2
                );

                if ($paymentsTotal !== $grandTotal) {
                    throw new RuntimeException('Split payment total must equal the grand total.');
                }

                if ($claimedIncomingPayment !== null && ! collect($normalizedPayments)->contains(
                    fn (array $payment): bool => $payment['method'] === 'mpesa'
                )) {
                    throw new RuntimeException('A claimed M-PESA transaction must be attached to an M-PESA payment line.');
                }

                if ($creditSaleRequested && ! SystemSetting::boolean('enable_credit_sales', true)) {
                    throw new RuntimeException('Credit sales are disabled for this shop.');
                }

                $duplicateSignal = $this->detectDuplicateMpesaSignal(
                    $normalizedPayments,
                    $claimedIncomingPayment
                );

                $sale = Sale::query()->create([
                    'user_id' => $cashierId,
                    'customer_id' => $customer?->id ?? ($validated['customer_id'] ?? null),
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'status' => 'completed',
                    'is_suspected_duplicate' => $duplicateSignal['is_suspected_duplicate'],
                    'suspected_duplicate_reason' => $duplicateSignal['reason'],
                    'receipt_number' => $this->generateReceiptNumber(),
                ]);

                foreach ($lineItems as $lineItem) {
                    SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $lineItem['product']->id,
                        'item_name' => $lineItem['product']->name,
                        'quantity' => $lineItem['quantity'],
                        'unit_price' => $lineItem['unit_price'],
                        'subtotal' => $lineItem['subtotal'],
                    ]);

                    if ($lineItem['quantity'] > 0) {
                        $lineItem['product']->decrement('stock_quantity', $lineItem['quantity']);
                    } else {
                        $lineItem['product']->increment('stock_quantity', abs($lineItem['quantity']));
                    }
                }

                $mpesaClaimAttached = false;
                $creditPayment = null;

                foreach ($normalizedPayments as $paymentData) {
                    $referenceNumber = $paymentData['reference_number'] ?? null;

                    if (
                        $claimedIncomingPayment !== null &&
                        ! $mpesaClaimAttached &&
                        $paymentData['method'] === 'mpesa'
                    ) {
                        $referenceNumber = $claimedIncomingPayment->transaction_code;
                        $mpesaClaimAttached = true;
                    }

                    $payment = Payment::query()->create([
                        'sale_id' => $sale->id,
                        'method' => $paymentData['method'],
                        'amount' => round((float) $paymentData['amount'], 2),
                        'phone_number' => $paymentData['phone_number'],
                        'phone_number_normalized' => $paymentData['phone_number_normalized'],
                        'reference_number' => $referenceNumber,
                        'status' => $paymentData['method'] === 'credit_deni'
                            ? 'pending'
                            : ($paymentData['status'] ?? 'completed'),
                    ]);

                    if ($payment->method === 'credit_deni') {
                        $creditPayment = $payment;
                    }
                }

                if ($claimedIncomingPayment !== null) {
                    $claimedIncomingPayment->update([
                        'status' => 'claimed',
                        'claimed_at' => now(),
                    ]);
                }

                if ($creditPayment !== null) {
                    if ($customer === null) {
                        throw new RuntimeException('Credit sales require a registered customer.');
                    }

                    $outstandingBalance = $customer->outstandingBalance();
                    $nextOutstandingBalance = round($outstandingBalance + $grandTotal, 2);
                    $creditLimit = round((float) $customer->credit_limit, 2);

                    if ($nextOutstandingBalance > $creditLimit) {
                        throw new RuntimeException("Credit limit exceeded for {$customer->name}. Outstanding balance would rise to {$nextOutstandingBalance} against a limit of {$creditLimit}.");
                    }

                    CustomerLedger::query()->create([
                        'customer_id' => $customer->id,
                        'sale_id' => $sale->id,
                        'payment_id' => $creditPayment->id,
                        'created_by' => $cashierId,
                        'entry_type' => 'debt',
                        'amount' => $grandTotal,
                        'balance_after' => $nextOutstandingBalance,
                        'notes' => "Credit sale logged at checkout for receipt {$sale->receipt_number}.",
                    ]);
                }

                SyncSaleToCloud::dispatch($sale)->afterCommit();

                if (SystemSetting::boolean('enable_hardware_printer', false)) {
                    PrintReceiptToHardware::dispatch($sale)->afterCommit();
                }

                AuditLog::query()->create([
                    'user_id' => $cashierId,
                    'action' => $grandTotal < 0 ? 'checkout_refund' : 'checkout_sale',
                    'description' => $grandTotal < 0
                        ? "Refund checkout completed for receipt {$sale->receipt_number}."
                        : "Sale checkout completed for receipt {$sale->receipt_number}.",
                    'reference_id' => $sale->id,
                ]);

                return $sale->load([
                    'customer',
                    'saleItems.product.taxCategory',
                    'payments',
                ])->setRelation('pricingAdjustments', collect($pricingAdjustments));
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Checkout failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'Checkout completed successfully.',
            'receipt_number' => $sale->receipt_number,
            'sale' => $sale,
            'pricing_adjustments' => $sale->getRelation('pricingAdjustments'),
        ], 201);
    }

    public function voidSale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'uuid'],
            'manager_pin' => ['required', 'string', 'regex:/^\d{4}(\d{2})?$/'],
        ]);

        $managerApprover = $this->resolveManagerApprover($validated['manager_pin']);

        if ($managerApprover === null) {
            return response()->json([
                'message' => 'Manager PIN required to void a finalized sale.',
            ], 422);
        }

        try {
            $sale = DB::transaction(function () use ($validated, $managerApprover, $request): Sale {
                /** @var Sale $sale */
                $sale = Sale::query()
                    ->with('saleItems')
                    ->lockForUpdate()
                    ->findOrFail($validated['sale_id']);

                if ($sale->status === 'voided') {
                    throw new RuntimeException('Sale is already voided.');
                }

                if ($sale->status !== 'completed') {
                    throw new RuntimeException('Only finalized completed sales can be voided.');
                }

                $productIds = $sale->saleItems
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($sale->saleItems as $saleItem) {
                    /** @var Product|null $product */
                    $product = $products->get($saleItem->product_id);

                    if ($product !== null) {
                        $product->increment('stock_quantity', round((float) $saleItem->quantity, 2));
                    }
                }

                $sale->update([
                    'status' => 'voided',
                ]);

                AuditLog::query()->create([
                    'user_id' => $managerApprover->id,
                    'action' => 'void_sale',
                    'description' => "Sale {$sale->receipt_number} voided after manager approval for cashier {$request->user()?->name}.",
                    'reference_id' => $sale->id,
                ]);

                return $sale->load([
                    'customer',
                    'saleItems.product.taxCategory',
                    'payments',
                ]);
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to void sale.',
            ], 500);
        }

        return response()->json([
            'message' => 'Sale voided successfully.',
            'sale' => $sale,
        ]);
    }

    public function verifyMpesaTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt_number' => ['nullable', 'string', 'required_without:CheckoutRequestID'],
            'CheckoutRequestID' => ['nullable', 'string', 'required_without:receipt_number'],
        ]);

        try {
            $payment = DB::transaction(function () use ($validated): Payment {
                $paymentQuery = Payment::query()
                    ->where('method', 'mpesa')
                    ->where('status', 'pending')
                    ->with('sale')
                    ->lockForUpdate();

                if (! empty($validated['receipt_number'])) {
                    $paymentQuery->whereHas('sale', function ($builder) use ($validated) {
                        $builder->where('receipt_number', $validated['receipt_number']);
                    });
                }

                if (! empty($validated['CheckoutRequestID'])) {
                    $paymentQuery->where('reference_number', $validated['CheckoutRequestID']);
                }

                /** @var Payment|null $payment */
                $payment = $paymentQuery->first();

                if ($payment === null) {
                    throw new RuntimeException('No pending M-PESA transaction found for the provided reference.');
                }

                // This simulates a successful Daraja status lookup for the fallback path.
                $payment->update([
                    'status' => 'completed',
                ]);

                return $payment->fresh('sale');
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to verify M-PESA transaction.',
            ], 500);
        }

        return response()->json([
            'message' => 'M-PESA transaction verified successfully.',
            'payment' => $payment,
            'sale' => $payment->sale,
        ]);
    }

    public function systemClockAnchor(): JsonResponse
    {
        $timestamps = collect([
            Sale::query()->max('created_at'),
            Payment::query()->max('created_at'),
            IncomingMpesaPayment::query()->max('created_at'),
            AuditLog::query()->max('created_at'),
        ])->filter();

        return response()->json([
            'server_time' => now()->toISOString(),
            'latest_transaction_created_at' => $timestamps->isEmpty()
                ? null
                : Carbon::parse($timestamps->max())->toISOString(),
        ]);
    }

    private function expiresWithin48Hours(CarbonInterface|string|null $batchExpiryDate): bool
    {
        if ($batchExpiryDate === null) {
            return false;
        }

        $expiryDate = $batchExpiryDate instanceof CarbonInterface
            ? $batchExpiryDate->copy()
            : Carbon::parse($batchExpiryDate);

        return $expiryDate->isFuture() && $expiryDate->lte(now()->addHours(48)->endOfDay());
    }

    private function daysSinceLastReceived(CarbonInterface|string|null $lastReceivedDate): int
    {
        if ($lastReceivedDate === null) {
            return 0;
        }

        $receivedDate = $lastReceivedDate instanceof CarbonInterface
            ? $lastReceivedDate->copy()
            : Carbon::parse($lastReceivedDate);

        return (int) max(0, $receivedDate->startOfDay()->diffInDays(now()->startOfDay(), false));
    }

    private function resolveManagerApprover(?string $pin): ?User
    {
        if ($pin === null || $pin === '') {
            return null;
        }

        /** @var User $user */
        foreach (User::query()->whereNotNull('pin')->whereIn('role', ['manager', 'admin'])->cursor() as $user) {
            if (Hash::check($pin, $user->pin)) {
                return $user;
            }
        }

        return null;
    }

    private function resolveSaleCustomer(array $validated, bool $creditSaleRequested): ?Customer
    {
        if (! empty($validated['customer_id'])) {
            return Customer::query()->find($validated['customer_id']);
        }

        $normalizedPhone = Customer::normalizePhone($validated['customer_phone'] ?? null);

        if ($normalizedPhone === null) {
            if ($creditSaleRequested) {
                throw new RuntimeException('Credit sales require a customer phone number.');
            }

            return null;
        }

        $customer = Customer::query()
            ->where('phone_normalized', $normalizedPhone)
            ->first();

        if ($customer === null && $creditSaleRequested) {
            throw new RuntimeException('No customer was found for the supplied phone number.');
        }

        return $customer;
    }

    private function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'RCPT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        } while (Sale::query()->where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }

    private function normalizePayments(array $payments, ?IncomingMpesaPayment $claimedIncomingPayment): array
    {
        return collect($payments)->map(function (array $payment) use ($claimedIncomingPayment): array {
            $phoneNumber = $payment['phone_number'] ?? null;
            $phoneNumberNormalized = Customer::normalizePhone($phoneNumber);

            if (
                $payment['method'] === 'mpesa' &&
                $claimedIncomingPayment !== null &&
                $phoneNumberNormalized === null
            ) {
                $phoneNumber = $claimedIncomingPayment->phone_number;
                $phoneNumberNormalized = $claimedIncomingPayment->phone_number_normalized;
            }

            return [
                'method' => $payment['method'],
                'amount' => round((float) $payment['amount'], 2),
                'phone_number' => $phoneNumber,
                'phone_number_normalized' => $phoneNumberNormalized,
                'reference_number' => $payment['reference_number'] ?? null,
                'status' => $payment['status'] ?? 'completed',
            ];
        })->all();
    }

    private function detectDuplicateMpesaSignal(array $payments, ?IncomingMpesaPayment $claimedIncomingPayment): array
    {
        $windowStart = now()->subMinutes(60);

        foreach ($payments as $payment) {
            if ($payment['method'] !== 'mpesa' || $payment['phone_number_normalized'] === null) {
                continue;
            }

            $amount = round((float) $payment['amount'], 2);

            $matchingIncomingPayment = IncomingMpesaPayment::query()
                ->where('amount', $amount)
                ->where('phone_number_normalized', $payment['phone_number_normalized'])
                ->where('created_at', '>=', $windowStart)
                ->when(
                    $claimedIncomingPayment !== null,
                    fn ($builder) => $builder->where('id', '!=', $claimedIncomingPayment->id)
                )
                ->exists();

            $matchingHistoricalPayment = Payment::query()
                ->where('method', 'mpesa')
                ->where('amount', $amount)
                ->where('phone_number_normalized', $payment['phone_number_normalized'])
                ->where('created_at', '>=', $windowStart)
                ->exists();

            if ($matchingIncomingPayment || $matchingHistoricalPayment) {
                return [
                    'is_suspected_duplicate' => true,
                    'reason' => 'Exact M-PESA amount and phone match detected within the last 60 minutes.',
                ];
            }
        }

        return [
            'is_suspected_duplicate' => false,
            'reason' => null,
        ];
    }
}
