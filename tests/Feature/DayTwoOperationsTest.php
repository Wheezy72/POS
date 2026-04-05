<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SystemSetting;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayTwoOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_pin_login_locks_the_terminal_after_three_failed_attempts(): void
    {
        foreach (range(1, 3) as $attempt) {
            $response = $this->postJson('/api/login-pin', [
                'pin' => '9999',
            ]);

            $response->assertStatus(422);
        }

        $lockedResponse = $this->postJson('/api/login-pin', [
            'pin' => '9999',
        ]);

        $lockedResponse->assertStatus(429);
        $lockedResponse->assertJsonPath('message', 'Too many PIN login attempts. Terminal locked for 60 seconds.');
    }

    public function test_credit_sale_is_blocked_when_customer_limit_would_be_exceeded(): void
    {
        SystemSetting::query()->create([
            'key' => 'enable_credit_sales',
            'value' => 'true',
        ]);

        $cashier = User::query()->create([
            'name' => 'Front Counter',
            'email' => 'cashier@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Amina Wanjiku',
            'phone' => '254712345678',
            'phone_normalized' => '254712345678',
            'credit_limit' => 100.00,
        ]);

        CustomerLedger::query()->create([
            'customer_id' => $customer->id,
            'entry_type' => 'debt',
            'amount' => 80.00,
            'balance_after' => 80.00,
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
            'base_price' => 50.00,
            'cost_price' => 30.00,
            'stock_quantity' => 5.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDays(5)->toDateString(),
            'batch_expiry_date' => now()->addDays(8)->toDateString(),
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson('/api/pos/checkout', [
                'customer_phone' => '0712345678',
                'cart' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payments' => [
                    [
                        'method' => 'credit_deni',
                        'amount' => 50.00,
                        'reference_number' => '0712345678',
                        'status' => 'pending',
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Credit limit exceeded for Amina Wanjiku. Outstanding balance would rise to 130 against a limit of 100.');
    }

    public function test_close_shift_calculates_variance_server_side_without_exposing_it_in_response(): void
    {
        $cashier = User::query()->create([
            'name' => 'Front Counter',
            'email' => 'cashier@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $this->actingAs($cashier)->postJson('/api/shifts/open', [
            'opening_cash' => 200.00,
        ])->assertCreated();

        $sale = Sale::query()->create([
            'user_id' => $cashier->id,
            'customer_id' => null,
            'subtotal' => 300.00,
            'tax_total' => 0.00,
            'discount_total' => 0.00,
            'grand_total' => 300.00,
            'status' => 'completed',
            'receipt_number' => 'SHIFT-TEST-0001',
        ]);

        Payment::query()->create([
            'sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 300.00,
            'reference_number' => null,
            'status' => 'completed',
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson('/api/shifts/close', [
                'counted_cash' => 470.00,
            ]);

        $response->assertOk();
        $response->assertJsonMissingPath('variance');

        $this->assertDatabaseHas('shifts', [
            'cashier_id' => $cashier->id,
            'expected_cash' => '500.00',
            'counted_cash' => '470.00',
            'variance' => '-30.00',
        ]);
    }
}
