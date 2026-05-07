<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue as QueueFacade;
use Tests\TestCase;

class PosCheckoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_checkout_id_returns_existing_sale_without_double_charging_stock(): void
    {
        QueueFacade::fake();

        [$cashier, $product] = $this->cashierAndProduct(stockQuantity: 2);

        $payload = [
            'client_checkout_id' => 'register-a-sale-0001',
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
        ];

        $firstResponse = $this->actingAs($cashier)->postJson('/api/pos/checkout', $payload);
        $secondResponse = $this->actingAs($cashier)->postJson('/api/pos/checkout', $payload);

        $firstResponse->assertCreated();
        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('message', 'Checkout already processed.');
        $secondResponse->assertJsonPath('receipt_number', $firstResponse->json('receipt_number'));

        $this->assertSame(1, Sale::query()->count());
        $this->assertEquals(1.00, (float) $product->fresh()->stock_quantity);
    }

    public function test_atomic_stock_update_blocks_stale_second_checkout(): void
    {
        QueueFacade::fake();

        [$cashier, $product] = $this->cashierAndProduct(stockQuantity: 1);

        $payload = fn (string $checkoutId): array => [
            'client_checkout_id' => $checkoutId,
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
        ];

        $this->actingAs($cashier)->postJson('/api/pos/checkout', $payload('checkout-one'))->assertCreated();
        $this->actingAs($cashier)->postJson('/api/pos/checkout', $payload('checkout-two'))->assertUnprocessable();

        $this->assertSame(1, Sale::query()->count());
        $this->assertEquals(0.00, (float) $product->fresh()->stock_quantity);
    }

    private function cashierAndProduct(float $stockQuantity): array
    {
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
            'name' => 'Safety Test Item',
            'sku' => 'SAFETY-ITEM',
            'barcode' => '7000000000001',
            'base_price' => 100.00,
            'cost_price' => 50.00,
            'stock_quantity' => $stockQuantity,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->toDateString(),
            'batch_expiry_date' => null,
        ]);

        return [$cashier, $product];
    }
}
