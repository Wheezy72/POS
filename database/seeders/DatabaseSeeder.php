<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $standardTax = TaxCategory::query()->updateOrCreate(
            ['name' => 'Standard VAT'],
            ['rate' => 16.00],
        );

        $luxuryTax = TaxCategory::query()->updateOrCreate(
            ['name' => 'Premium Goods VAT'],
            ['rate' => 16.00],
        );

        $zeroTax = TaxCategory::query()->updateOrCreate(
            ['name' => 'Zero Rated'],
            ['rate' => 0.00],
        );

        User::query()->updateOrCreate([
            'email' => 'admin@duka.app',
        ], [
            'name' => 'Admin Console',
            'password' => 'duka-admin-password',
            'pin' => '1234',
            'role' => 'admin',
        ]);

        User::query()->updateOrCreate([
            'email' => 'cashier@duka.app',
        ], [
            'name' => 'Front Counter',
            'password' => 'duka-cashier-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $products = [
            [
                'name' => 'MacBook Pro 16"',
                'sku' => 'MACBOOK-PRO-16',
                'barcode' => '8806095721001',
                'tax_category_id' => $luxuryTax->id,
                'base_price' => 389999.00,
                'cost_price' => 332000.00,
                'stock_quantity' => 6,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(12)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'AirPods Max',
                'sku' => 'AIRPODS-MAX',
                'barcode' => '8806095721002',
                'tax_category_id' => $luxuryTax->id,
                'base_price' => 82999.00,
                'cost_price' => 70200.00,
                'stock_quantity' => 10,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(18)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Tesla Key Card',
                'sku' => 'TESLA-KEY',
                'barcode' => '8806095721003',
                'tax_category_id' => $standardTax->id,
                'base_price' => 6499.00,
                'cost_price' => 5200.00,
                'stock_quantity' => 20,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(8)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Premium Coffee Beans 1kg',
                'sku' => 'COFFEE-BEANS-1KG',
                'barcode' => '8806095721004',
                'tax_category_id' => $zeroTax->id,
                'base_price' => 2850.00,
                'cost_price' => 1900.00,
                'stock_quantity' => 36,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(4)->toDateString(),
                'batch_expiry_date' => Carbon::now()->addMonths(10)->toDateString(),
            ],
            [
                'name' => 'Cold Brew Concentrate',
                'sku' => 'COLD-BREW-CONC',
                'barcode' => '8806095721005',
                'tax_category_id' => $zeroTax->id,
                'base_price' => 1450.00,
                'cost_price' => 980.00,
                'stock_quantity' => 18,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(2)->toDateString(),
                'batch_expiry_date' => Carbon::now()->addDays(20)->toDateString(),
            ],
            [
                'name' => 'Smart LED Desk Lamp',
                'sku' => 'SMART-DESK-LAMP',
                'barcode' => '8806095721006',
                'tax_category_id' => $standardTax->id,
                'base_price' => 8999.00,
                'cost_price' => 6200.00,
                'stock_quantity' => 14,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(27)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Noise Cancelling Travel Pillow',
                'sku' => 'TRAVEL-PILLOW-NC',
                'barcode' => '8806095721007',
                'tax_category_id' => $standardTax->id,
                'base_price' => 4999.00,
                'cost_price' => 3400.00,
                'stock_quantity' => 22,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(16)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Studio Monitor Headphones',
                'sku' => 'STUDIO-MONITOR-HP',
                'barcode' => '8806095721008',
                'tax_category_id' => $luxuryTax->id,
                'base_price' => 24999.00,
                'cost_price' => 18800.00,
                'stock_quantity' => 11,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(34)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Artisan Sparkling Water',
                'sku' => 'ARTISAN-SPARK-WATER',
                'barcode' => '8806095721009',
                'tax_category_id' => $zeroTax->id,
                'base_price' => 220.00,
                'cost_price' => 120.00,
                'stock_quantity' => 180,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(3)->toDateString(),
                'batch_expiry_date' => Carbon::now()->addMonths(6)->toDateString(),
            ],
            [
                'name' => 'Signature Leather Notebook',
                'sku' => 'LEATHER-NOTEBOOK',
                'barcode' => '8806095721010',
                'tax_category_id' => $standardTax->id,
                'base_price' => 3199.00,
                'cost_price' => 2150.00,
                'stock_quantity' => 28,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(42)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Wireless MagSafe Battery Pack',
                'sku' => 'MAGSAFE-BATTERY',
                'barcode' => '8806095721011',
                'tax_category_id' => $luxuryTax->id,
                'base_price' => 17999.00,
                'cost_price' => 14950.00,
                'stock_quantity' => 9,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(75)->toDateString(),
                'batch_expiry_date' => null,
            ],
            [
                'name' => 'Limited Reserve Matcha',
                'sku' => 'RESERVE-MATCHA',
                'barcode' => '8806095721012',
                'tax_category_id' => $zeroTax->id,
                'base_price' => 1899.00,
                'cost_price' => 1320.00,
                'stock_quantity' => 24,
                'allow_fractional_sales' => false,
                'is_composite' => false,
                'last_received_date' => Carbon::now()->subDays(7)->toDateString(),
                'batch_expiry_date' => Carbon::now()->addMonths(8)->toDateString(),
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate([
                'sku' => $product['sku'],
            ], $product + [
                'price_tiers' => null,
            ]);
        }
    }
}
