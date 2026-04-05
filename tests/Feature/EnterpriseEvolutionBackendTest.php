<?php

namespace Tests\Feature;

use App\Models\IncomingMpesaPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseEvolutionBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_flags_exact_mpesa_phone_and_amount_reuse_as_suspected_duplicate(): void
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
            'name' => 'Kabras Sugar 2kg',
            'sku' => 'KABRAS-2KG',
            'barcode' => '6161100101001',
            'base_price' => 200.00,
            'cost_price' => 150.00,
            'stock_quantity' => 20.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDays(3)->toDateString(),
            'batch_expiry_date' => now()->addDays(30)->toDateString(),
        ]);

        IncomingMpesaPayment::query()->create([
            'transaction_code' => 'QWE1234567',
            'customer_name' => 'Amina Wanjiku',
            'phone_number' => '254712345678',
            'phone_number_normalized' => '254712345678',
            'amount' => 200.00,
            'status' => 'pending',
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
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
                        'method' => 'mpesa',
                        'amount' => 200.00,
                        'phone_number' => '0712345678',
                        'reference_number' => 'STK-001',
                        'status' => 'completed',
                    ],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('sale.is_suspected_duplicate', true);
        $response->assertJsonPath(
            'sale.suspected_duplicate_reason',
            'Exact M-PESA amount and phone match detected within the last 60 minutes.'
        );
    }

    public function test_checkout_accepts_negative_quantities_for_returns_and_puts_stock_back(): void
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
            'name' => 'Brookside Milk 500ml',
            'sku' => 'BROOKSIDE-500ML',
            'barcode' => '6161100100020',
            'base_price' => 50.00,
            'cost_price' => 30.00,
            'stock_quantity' => 5.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDays(2)->toDateString(),
            'batch_expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson('/api/pos/checkout', [
                'cart' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => -1,
                    ],
                ],
                'payments' => [
                    [
                        'method' => 'cash',
                        'amount' => -50.00,
                        'reference_number' => null,
                        'status' => 'completed',
                    ],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('sale.grand_total', -50);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => '6.00',
        ]);
    }

    public function test_voiding_a_finalized_sale_requires_a_manager_pin_and_appends_an_audit_hash(): void
    {
        $cashier = User::query()->create([
            'name' => 'Front Counter',
            'email' => 'cashier@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        User::query()->create([
            'name' => 'Store Manager',
            'email' => 'manager@example.com',
            'password' => 'secret-password',
            'pin' => '2468',
            'role' => 'manager',
        ]);

        $taxCategory = TaxCategory::query()->create([
            'name' => 'Zero Rated',
            'rate' => 0.00,
        ]);

        $product = Product::query()->create([
            'tax_category_id' => $taxCategory->id,
            'name' => 'Jogoo Unga',
            'sku' => 'JOGOO-2KG',
            'barcode' => '6161100199999',
            'base_price' => 150.00,
            'cost_price' => 120.00,
            'stock_quantity' => 3.00,
            'allow_fractional_sales' => false,
            'price_tiers' => null,
            'is_composite' => false,
            'last_received_date' => now()->subDays(3)->toDateString(),
            'batch_expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        $sale = Sale::query()->create([
            'user_id' => $cashier->id,
            'customer_id' => null,
            'subtotal' => 150.00,
            'tax_total' => 0.00,
            'discount_total' => 0.00,
            'grand_total' => 150.00,
            'status' => 'completed',
            'receipt_number' => 'RCPT-VOID-0001',
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 150.00,
            'subtotal' => 150.00,
        ]);

        $this->actingAs($cashier)
            ->postJson('/api/pos/void-sale', [
                'sale_id' => $sale->id,
                'manager_pin' => '1111',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Manager PIN required to void a finalized sale.');

        $response = $this->actingAs($cashier)
            ->postJson('/api/pos/void-sale', [
                'sale_id' => $sale->id,
                'manager_pin' => '2468',
            ]);

        $response->assertOk();
        $response->assertJsonPath('sale.status', 'voided');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'void_sale',
            'reference_id' => $sale->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'void_sale',
            'integrity_hash' => null,
        ]);
    }

    public function test_cash_drawer_transactions_are_included_in_expected_cash_math(): void
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

        $this->actingAs($cashier)->postJson('/api/shifts/cash-drawer-transactions', [
            'type' => 'pay_in',
            'amount' => 50.00,
            'reason' => 'Add float',
        ])->assertCreated();

        $this->actingAs($cashier)->postJson('/api/shifts/cash-drawer-transactions', [
            'type' => 'pay_out',
            'amount' => 20.00,
            'reason' => 'Petty cash',
        ])->assertCreated();

        $sale = Sale::query()->create([
            'user_id' => $cashier->id,
            'customer_id' => null,
            'subtotal' => 300.00,
            'tax_total' => 0.00,
            'discount_total' => 0.00,
            'grand_total' => 300.00,
            'status' => 'completed',
            'receipt_number' => 'SHIFT-TEST-0002',
        ]);

        Payment::query()->create([
            'sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 300.00,
            'reference_number' => null,
            'status' => 'completed',
        ]);

        $this->actingAs($cashier)->postJson('/api/shifts/close', [
            'counted_cash' => 530.00,
        ])->assertOk();

        $this->assertDatabaseHas('shifts', [
            'cashier_id' => $cashier->id,
            'expected_cash' => '530.00',
            'counted_cash' => '530.00',
            'variance' => '0.00',
        ]);
    }

    public function test_backup_command_creates_a_zip_on_the_configured_disk(): void
    {
        Storage::fake('backups');

        $databasePath = storage_path('framework/testing-backup-database.sqlite');
        file_put_contents($databasePath, 'sqlite-backup-payload');

        config()->set('database.connections.sqlite.database', $databasePath);

        $exitCode = Artisan::call('app:backup-database', [
            '--disk' => 'backups',
            '--path' => 'db/test-backup.zip',
        ]);

        $this->assertSame(0, $exitCode);
        Storage::disk('backups')->assertExists('db/test-backup.zip');

        @unlink($databasePath);
    }
}
