<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApiController extends Controller
{
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'tax_category_id' => ['required', 'uuid', 'exists:tax_categories,id'],
            'allow_fractional_sales' => ['sometimes', 'boolean'],
            'price_tiers' => ['nullable', 'array'],
            'is_composite' => ['sometimes', 'boolean'],
        ]);

        $product = Product::query()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'base_price' => round((float) $validated['base_price'], 2),
            'stock_quantity' => round((float) ($validated['stock_quantity'] ?? 0), 2),
            'tax_category_id' => $validated['tax_category_id'],
            'allow_fractional_sales' => $validated['allow_fractional_sales'] ?? false,
            'price_tiers' => $validated['price_tiers'] ?? null,
            'is_composite' => $validated['is_composite'] ?? false,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load('taxCategory'),
        ], 201);
    }

    public function updateProduct(Request $request, string $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product->id)],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'numeric', 'min:0'],
            'tax_category_id' => ['sometimes', 'uuid', 'exists:tax_categories,id'],
            'allow_fractional_sales' => ['sometimes', 'boolean'],
            'price_tiers' => ['nullable', 'array'],
            'is_composite' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('base_price', $validated)) {
            $validated['base_price'] = round((float) $validated['base_price'], 2);
        }

        if (array_key_exists('stock_quantity', $validated)) {
            $validated['stock_quantity'] = round((float) $validated['stock_quantity'], 2);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh()->load('taxCategory'),
        ]);
    }

    public function storeCashier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:cashier'],
            'pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ]);

        // The current users table still requires unique email and password columns from
        // the stock Laravel scaffold, so this endpoint provisions safe placeholder values
        // while keeping the actual POS login mechanism PIN-based.
        $emailLocalPart = Str::slug($validated['name']);
        $emailLocalPart = $emailLocalPart !== '' ? $emailLocalPart : 'cashier';

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $emailLocalPart . '+' . Str::lower(Str::random(10)) . '@duka.local',
            'password' => Hash::make(Str::random(40)),
            'role' => $validated['role'],
            'pin' => Hash::make($validated['pin']),
        ]);

        return response()->json([
            'message' => 'Cashier registered successfully.',
            'cashier' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function dailySummary(): JsonResponse
    {
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $completedSales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        $totalRevenue = round((float) (clone $completedSales)->sum('grand_total'), 2);
        $totalTax = round((float) (clone $completedSales)->sum('tax_total'), 2);

        $paymentsQuery = Payment::query()
            ->where('status', 'completed')
            ->whereHas('sale', function ($builder) use ($startOfDay, $endOfDay) {
                $builder
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startOfDay, $endOfDay]);
            });

        $cashTotal = round((float) (clone $paymentsQuery)->where('method', 'cash')->sum('amount'), 2);
        $mpesaTotal = round((float) (clone $paymentsQuery)->where('method', 'mpesa')->sum('amount'), 2);

        $voidsExecuted = Sale::query()
            ->where('status', 'voided')
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->count();

        return response()->json([
            'date' => $startOfDay->toDateString(),
            'total_revenue' => $totalRevenue,
            'total_tax' => $totalTax,
            'payment_split' => [
                'cash' => $cashTotal,
                'mpesa' => $mpesaTotal,
            ],
            'voids_executed' => $voidsExecuted,
        ]);
    }
}
