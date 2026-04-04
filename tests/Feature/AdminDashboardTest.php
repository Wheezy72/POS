<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_finance_unlock_screen(): void
    {
        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSeeText('Unlock the CFO Console');
        $response->assertSeeText('Demo finance PIN');
    }

    public function test_admin_can_fetch_dashboard_overview(): void
    {
        $admin = User::query()->create([
            'name' => 'CFO Console',
            'email' => 'finance@example.com',
            'password' => 'secret-password',
            'pin' => '9999',
            'role' => 'admin',
        ]);

        $taxCategory = TaxCategory::query()->create([
            'name' => 'Standard VAT',
            'rate' => 16.00,
        ]);

        $product = Product::query()->create([
            'tax_category_id' => $taxCategory->id,
            'name' => 'Brookside Long Life Milk 500ml',
            'sku' => 'BROOKSIDE-500ML',
            'barcode' => '6161100100020',
            'base_price' => 40.00,
            'cost_price' => 35.00,
            'stock_quantity' => 1.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDay()->toDateString(),
            'batch_expiry_date' => now()->addDays(7)->toDateString(),
        ]);

        $sale = Sale::query()->create([
            'user_id' => $admin->id,
            'customer_id' => null,
            'subtotal' => 160.00,
            'tax_total' => 25.60,
            'discount_total' => 0.00,
            'grand_total' => 185.60,
            'status' => 'completed',
            'receipt_number' => 'RCPT-TEST-0001',
            'created_at' => now()->setTime(18, 15),
            'updated_at' => now()->setTime(18, 15),
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'quantity' => 4,
            'unit_price' => 40.00,
            'subtotal' => 160.00,
            'created_at' => $sale->created_at,
            'updated_at' => $sale->updated_at,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/admin/reports/dashboard-overview');

        $response->assertOk();
        $response->assertJsonPath('kpis.month_revenue', 160);
        $response->assertJsonPath('kpis.month_profit', 20);
        $response->assertJsonPath('kpis.critical_alert_count', 1);
        $response->assertJsonPath('critical_alerts.0.sku', 'BROOKSIDE-500ML');
        $response->assertJsonCount(7, 'profit_vs_revenue.labels');
        $response->assertJsonCount(24, 'hourly_heatmap.labels');
    }
}
