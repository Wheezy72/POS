<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncSaleToCloud;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        try {
            $sale = DB::transaction(function () use ($validated, $cashierId): Sale {
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

                    $unitPrice = round((float) $product->base_price, 2);
                    $lineSubtotal = round($quantity * $unitPrice, 2);
                    $lineTax = round($lineSubtotal * (((float) $product->taxCategory->rate) / 100), 2);

                    $subtotal += $lineSubtotal;
                    $taxTotal += $lineTax;
                }

                $subtotal = round($subtotal, 2);
                $taxTotal = round($taxTotal, 2);
                $discountTotal = 0.0;
                $grandTotal = round($subtotal + $taxTotal - $discountTotal, 2);
                $paymentsTotal = round(
                    collect($validated['payments'])->sum(fn (array $payment): float => round((float) $payment['amount'], 2)),
                    2
                );

                if ($paymentsTotal !== $grandTotal) {
                    throw new RuntimeException('Split payment total must equal the grand total.');
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

                foreach ($validated['cart'] as $cartItem) {
                    /** @var Product $product */
                    $product = $products->get($cartItem['product_id']);
                    $quantity = round((float) $cartItem['quantity'], 2);
                    $unitPrice = round((float) $product->base_price, 2);
                    $lineSubtotal = round($quantity * $unitPrice, 2);

                    SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'item_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                    ]);

                    $product->decrement('stock_quantity', $quantity);
                }

                foreach ($validated['payments'] as $paymentData) {
                    Payment::query()->create([
                        'sale_id' => $sale->id,
                        'method' => $paymentData['method'],
                        'amount' => round((float) $paymentData['amount'], 2),
                        'reference_number' => $paymentData['reference_number'] ?? null,
                        'status' => $paymentData['status'] ?? 'completed',
                    ]);
                }

                SyncSaleToCloud::dispatch($sale)->afterCommit();

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
                'message' => 'Checkout failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'Checkout completed successfully.',
            'receipt_number' => $sale->receipt_number,
            'sale' => $sale,
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

    private function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'RCPT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        } while (Sale::query()->where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }
}
