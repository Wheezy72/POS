<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue as QueueFacade;
use Tests\TestCase;

class PosCheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_returns_server_side_pricing_adjustments_when_margin_floor_is_applied(): void
    {
        QueueFacade::fake();

        $cashier = User::query()->create([
            'name' => 'Front Counter',
            'email' => 'cashier@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $taxCategory = TaxCategory::query()->create([
            'name' => 'Zero Rated',
            'rate' => 0.00,
        ]);

        $product = Product::query()->create([
            'tax_category_id' => $taxCategory->id,
            'name' => 'Brookside Long Life Milk 500ml',
            'sku' => 'BROOKSIDE-500ML',
            'barcode' => '6161100100020',
            'base_price' => 100.00,
            'cost_price' => 90.00,
            'stock_quantity' => 5.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDays(5)->toDateString(),
            'batch_expiry_date' => now()->addDay()->toDateString(),
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson('/api/pos/checkout', [
                'cart' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payments' => [
                    [
                        'method' => 'cash',
                        'amount' => 100.00,
                        'reference_number' => null,
                        'status' => 'completed',
                    ],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('pricing_adjustments.0.product_name', 'Brookside Long Life Milk 500ml');
        $response->assertJsonPath('pricing_adjustments.0.price_source', 'margin_floor');
        $response->assertJsonPath('pricing_adjustments.0.base_unit_price', 100);
        $response->assertJsonPath('pricing_adjustments.0.final_unit_price', 103.5);
    }
}
