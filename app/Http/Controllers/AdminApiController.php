<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'tax_category_id' => ['required', 'uuid', 'exists:tax_categories,id'],
            'allow_fractional_sales' => ['sometimes', 'boolean'],
            'price_tiers' => ['nullable', 'array'],
            'is_composite' => ['sometimes', 'boolean'],
            'last_received_date' => ['nullable', 'date'],
            'batch_expiry_date' => ['nullable', 'date'],
        ]);

        $product = Product::query()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'base_price' => round((float) $validated['base_price'], 2),
            'cost_price' => round((float) ($validated['cost_price'] ?? 0), 2),
            'stock_quantity' => round((float) ($validated['stock_quantity'] ?? 0), 2),
            'category_id' => $validated['category_id'] ?? null,
            'tax_category_id' => $validated['tax_category_id'],
            'allow_fractional_sales' => $validated['allow_fractional_sales'] ?? false,
            'price_tiers' => $validated['price_tiers'] ?? null,
            'is_composite' => $validated['is_composite'] ?? false,
            'last_received_date' => $validated['last_received_date'] ?? null,
            'batch_expiry_date' => $validated['batch_expiry_date'] ?? null,
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
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'tax_category_id' => ['sometimes', 'uuid', 'exists:tax_categories,id'],
            'allow_fractional_sales' => ['sometimes', 'boolean'],
            'price_tiers' => ['nullable', 'array'],
            'is_composite' => ['sometimes', 'boolean'],
            'last_received_date' => ['nullable', 'date'],
            'batch_expiry_date' => ['nullable', 'date'],
        ]);

        if (array_key_exists('base_price', $validated)) {
            $validated['base_price'] = round((float) $validated['base_price'], 2);
        }

        if (array_key_exists('cost_price', $validated)) {
            $validated['cost_price'] = round((float) $validated['cost_price'], 2);
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

    public function dashboardOverview(): JsonResponse
    {
        $startOfWindow = now()->subDays(6)->startOfDay();
        $endOfWindow = now()->endOfDay();
        $hourBucketExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%H', created_at)"
            : 'LPAD(HOUR(created_at), 2, "0")';

        $dailyFinancials = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startOfWindow, $endOfWindow])
            ->selectRaw("date(sales.created_at) as day_bucket")
            ->selectRaw('ROUND(SUM(sale_items.subtotal), 2) as revenue')
            ->selectRaw('ROUND(SUM((sale_items.unit_price - products.cost_price) * sale_items.quantity), 2) as profit')
            ->groupBy('day_bucket')
            ->orderBy('day_bucket')
            ->get()
            ->keyBy('day_bucket');

        $chartLabels = [];
        $revenueSeries = [];
        $profitSeries = [];

        foreach (range(6, 0) as $dayOffset) {
            $day = now()->subDays($dayOffset)->toDateString();
            $chartLabels[] = now()->subDays($dayOffset)->format('D');
            $revenueSeries[] = round((float) ($dailyFinancials[$day]->revenue ?? 0), 2);
            $profitSeries[] = round((float) ($dailyFinancials[$day]->profit ?? 0), 2);
        }

        $soldQuantities = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity_sold'))
            ->whereHas('sale', function ($builder) use ($startOfWindow, $endOfWindow) {
                $builder
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startOfWindow, $endOfWindow]);
            })
            ->groupBy('product_id')
            ->pluck('total_quantity_sold', 'product_id');

        $criticalAlerts = Product::query()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($soldQuantities) {
                $quantitySold = round((float) ($soldQuantities[$product->id] ?? 0), 2);
                $averageDailySold = round($quantitySold / 7, 2);
                $runwayDays = $averageDailySold > 0
                    ? round(((float) $product->stock_quantity) / $averageDailySold, 2)
                    : null;

                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock_quantity' => round((float) $product->stock_quantity, 2),
                    'average_daily_quantity_sold' => $averageDailySold,
                    'runway_days' => $runwayDays,
                ];
            })
            ->filter(fn (array $product): bool => $product['runway_days'] !== null && $product['runway_days'] < 3)
            ->sortBy('runway_days')
            ->take(6)
            ->values();

        $hourlyFinancials = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subDays(30)->startOfDay(), now()->endOfDay()])
            ->selectRaw("{$hourBucketExpression} as hour_bucket")
            ->selectRaw('ROUND(SUM(grand_total), 2) as total_revenue')
            ->groupBy('hour_bucket')
            ->orderBy('hour_bucket')
            ->get()
            ->keyBy('hour_bucket');

        $hourLabels = [];
        $hourRevenueSeries = [];

        foreach (range(0, 23) as $hour) {
            $bucket = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
            $hourLabels[] = $bucket . ':00';
            $hourRevenueSeries[] = round((float) ($hourlyFinancials[$bucket]->total_revenue ?? 0), 2);
        }

        $monthFinancials = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('ROUND(SUM(sale_items.subtotal), 2) as revenue')
            ->selectRaw('ROUND(SUM((sale_items.unit_price - products.cost_price) * sale_items.quantity), 2) as profit')
            ->first();

        return response()->json([
            'kpis' => [
                'month_revenue' => round((float) ($monthFinancials?->revenue ?? 0), 2),
                'month_profit' => round((float) ($monthFinancials?->profit ?? 0), 2),
                'critical_alert_count' => $criticalAlerts->count(),
            ],
            'profit_vs_revenue' => [
                'labels' => $chartLabels,
                'revenue' => $revenueSeries,
                'profit' => $profitSeries,
            ],
            'critical_alerts' => $criticalAlerts,
            'hourly_heatmap' => [
                'labels' => $hourLabels,
                'revenue' => $hourRevenueSeries,
            ],
        ]);
    }

    public function inventoryVelocity(): JsonResponse
    {
        $startOfWindow = now()->subDays(7)->startOfDay();
        $endOfWindow = now()->endOfDay();

        $soldQuantities = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity_sold'))
            ->whereHas('sale', function ($builder) use ($startOfWindow, $endOfWindow) {
                $builder
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startOfWindow, $endOfWindow]);
            })
            ->groupBy('product_id')
            ->pluck('total_quantity_sold', 'product_id');

        $products = Product::query()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($soldQuantities) {
                $quantitySold = round((float) ($soldQuantities[$product->id] ?? 0), 2);
                $averageDailySold = round($quantitySold / 7, 2);
                $runwayDays = $averageDailySold > 0
                    ? round(((float) $product->stock_quantity) / $averageDailySold, 2)
                    : null;

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock_quantity' => round((float) $product->stock_quantity, 2),
                    'average_daily_quantity_sold' => $averageDailySold,
                    'runway_days' => $runwayDays,
                ];
            });

        return response()->json([
            'window_days' => 7,
            'inventory_velocity' => $products,
        ]);
    }

    public function marginBleed(): JsonResponse
    {
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $lineMetrics = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startOfDay, $endOfDay])
            ->selectRaw('COALESCE(SUM(products.base_price * sale_items.quantity), 0) as expected_revenue')
            ->selectRaw('COALESCE(SUM(products.cost_price * sale_items.quantity), 0) as total_cost')
            ->first();

        $expectedRevenue = round((float) ($lineMetrics?->expected_revenue ?? 0), 2);
        $totalCost = round((float) ($lineMetrics?->total_cost ?? 0), 2);
        $actualRevenue = round((float) Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('grand_total'), 2);

        $totalDiscountValue = round($expectedRevenue - $actualRevenue, 2);
        $effectiveGrossMargin = $actualRevenue > 0
            ? round((($actualRevenue - $totalCost) / $actualRevenue) * 100, 2)
            : 0.0;

        return response()->json([
            'date' => $startOfDay->toDateString(),
            'expected_revenue' => $expectedRevenue,
            'actual_revenue' => $actualRevenue,
            'total_discount_value' => $totalDiscountValue,
            'effective_gross_margin_percent' => $effectiveGrossMargin,
        ]);
    }

    public function peakHours(): JsonResponse
    {
        $peakHours = Sale::query()
            ->where('status', 'completed')
            ->selectRaw("strftime('%H', created_at) as hour_bucket")
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('ROUND(SUM(grand_total), 2) as total_revenue')
            ->groupBy('hour_bucket')
            ->orderByDesc('sales_count')
            ->orderBy('hour_bucket')
            ->get()
            ->map(function ($row) {
                return [
                    'hour' => $row->hour_bucket,
                    'sales_count' => (int) $row->sales_count,
                    'total_revenue' => round((float) $row->total_revenue, 2),
                ];
            })
            ->values();

        return response()->json([
            'peak_hours' => $peakHours,
        ]);
    }
}
