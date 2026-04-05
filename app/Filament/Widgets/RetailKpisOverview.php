<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RetailKpisOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $dailyRevenue = round((float) Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$today, $endOfDay])
            ->sum('grand_total'), 2);

        $inventoryValuation = round((float) Product::query()
            ->selectRaw('SUM(cost_price * stock_quantity) as valuation')
            ->value('valuation'), 2);

        $criticalStockAlerts = Product::query()
            ->where('stock_quantity', '<', 10)
            ->count();

        $netProfit = round((float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$today, $endOfDay])
            ->select(DB::raw('COALESCE(SUM((sale_items.unit_price - products.cost_price) * sale_items.quantity), 0) as net_profit'))
            ->value('net_profit'), 2);

        return [
            Stat::make('Daily Revenue', 'KES ' . number_format($dailyRevenue, 2))
                ->description('Completed sales for today')
                ->color('success'),
            Stat::make('Inventory Valuation', 'KES ' . number_format($inventoryValuation, 2))
                ->description('Cost value of current stock')
                ->color('info'),
            Stat::make('Critical Stock Alerts', number_format($criticalStockAlerts))
                ->description('Products with stock below 10')
                ->color($criticalStockAlerts > 0 ? 'danger' : 'success'),
            Stat::make('Net Profit', 'KES ' . number_format($netProfit, 2))
                ->description('Today: unit price minus cost')
                ->color('warning'),
        ];
    }
}
