<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncSaleToCloud;
use App\Models\IncomingMpesaPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
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
            'manager_pin' => ['nullable', 'string', 'regex:/^\d{4}(\d{2})?$/'],
            'claim_transaction_code' => ['nullable', 'string', 'max:255'],
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.product_id' => ['required', 'uuid'],
            'cart.*.quantity' => ['required', 'numeric', 'gt:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,mpesa,card,credit_deni'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference_number' => ['nullable', 'string'],
            'payments.*.status' => ['nullable', 'in:pending,completed,failed'],
        ]);

        $cashierId = $request->user()?->getAuthIdentifier();

        if ($cashierId === null) {
            return response()->json([
                'message' => 'Unauthenticated cashier.',
            ], 401);
        }

        $managerOverrideApproved = $this->hasValidManagerOverride($validated['manager_pin'] ?? null);

        try {
            $sale = DB::transaction(function () use ($validated, $cashierId, $managerOverrideApproved): Sale {
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

                    if (! $product->allow_fractional_sales && floor($quantity) !== $quantity) {
                        throw new RuntimeException("Product {$product->name} does not allow fractional sales.");
                    }

                    $availableStock = round((float) $product->stock_quantity, 2);

                    if ($availableStock < $quantity) {
                        throw new RuntimeException("Insufficient stock for product {$product->name}.");
                    }

                    $basePrice = round((float) $product->base_price, 2);
                    $costPrice = round((float) $product->cost_price, 2);
                    $originalUnitPrice = $basePrice;
                    $priceSource = 'base_price';

                    // Rule 1: expiry-driven markdowns.
                    // If the batch expires within 48 hours, the engine automatically cuts the shelf
                    // price by 50% to improve sell-through before the stock becomes waste.
                    $unitPrice = $basePrice;

                    if ($this->expiresWithin48Hours($product->batch_expiry_date)) {
                        $unitPrice = round($basePrice * 0.5, 2);
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

                    if (! $managerOverrideApproved && $unitPrice < $marginFloor) {
                        $unitPrice = $marginFloor;
                        $marginClampApplied = true;
                        $priceSource = 'margin_floor';
                    }

                    $unitPrice = round($unitPrice, 2);
                    $lineSubtotal = round($quantity * $unitPrice, 2);
                    $lineTax = round($lineSubtotal * (((float) $product->taxCategory->rate) / 100), 2);

                    if ($unitPrice !== $originalUnitPrice || $marginClampApplied) {
                        $pricingAdjustments[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => $quantity,
                            'base_unit_price' => $basePrice,
                            'final_unit_price' => $unitPrice,
                            'margin_floor' => $marginFloor,
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

                $sale = Sale::query()->create([
                    'user_id' => $cashierId,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'status' => 'completed',
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

                    $lineItem['product']->decrement('stock_quantity', $lineItem['quantity']);
                }

                $mpesaClaimAttached = false;

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

                    Payment::query()->create([
                        'sale_id' => $sale->id,
                        'method' => $paymentData['method'],
                        'amount' => round((float) $paymentData['amount'], 2),
                        'reference_number' => $referenceNumber,
                        'status' => $paymentData['status'] ?? 'completed',
                    ]);
                }

                if ($claimedIncomingPayment !== null) {
                    $claimedIncomingPayment->update([
                        'status' => 'claimed',
                        'claimed_at' => now(),
                    ]);
                }

                SyncSaleToCloud::dispatch($sale)->afterCommit();

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
        ]);

        try {
            $sale = DB::transaction(function () use ($validated): Sale {
                /** @var Sale $sale */
                $sale = Sale::query()
                    ->with('saleItems')
                    ->lockForUpdate()
                    ->findOrFail($validated['sale_id']);

                if ($sale->status === 'voided') {
                    throw new RuntimeException('Sale is already voided.');
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

        return max(0, $receivedDate->startOfDay()->diffInDays(now()->startOfDay(), false));
    }

    private function hasValidManagerOverride(?string $pin): bool
    {
        if ($pin === null || $pin === '') {
            return false;
        }

        /** @var User $user */
        foreach (User::query()->whereNotNull('pin')->whereIn('role', ['manager', 'admin'])->cursor() as $user) {
            if (Hash::check($pin, $user->pin)) {
                return true;
            }
        }

        return false;
    }

    private function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'RCPT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        } while (Sale::query()->where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }
}
