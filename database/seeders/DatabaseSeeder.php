<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\IncomingMpesaPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $taxCategories = $this->seedTaxCategories();
            $users = $this->seedUsers();
            $products = $this->seedProducts($taxCategories);

            $this->resetOperationalData();
            $customers = $this->seedCustomers();
            $this->seedHistoricalSales($products, $customers, $users['cashiers']);
            $this->seedIncomingMpesaFeed();
        });
    }

    private function seedTaxCategories(): array
    {
        return [
            'standard' => TaxCategory::query()->updateOrCreate(
                ['name' => 'Standard VAT'],
                ['rate' => 16.00],
            ),
            'zero' => TaxCategory::query()->updateOrCreate(
                ['name' => 'Zero Rated'],
                ['rate' => 0.00],
            ),
            'fuel' => TaxCategory::query()->updateOrCreate(
                ['name' => 'Fuel VAT'],
                ['rate' => 8.00],
            ),
        ];
    }

    private function seedUsers(): array
    {
        $admin = User::query()->updateOrCreate([
            'email' => 'finance@duka.app',
        ], [
            'name' => 'CFO Console',
            'password' => 'duka-finance-password',
            'pin' => '9999',
            'role' => 'admin',
        ]);

        $manager = User::query()->updateOrCreate([
            'email' => 'manager@duka.app',
        ], [
            'name' => 'Floor Manager',
            'password' => 'duka-manager-password',
            'pin' => '2468',
            'role' => 'manager',
        ]);

        $frontCounter = User::query()->updateOrCreate([
            'email' => 'cashier@duka.app',
        ], [
            'name' => 'Front Counter',
            'password' => 'duka-cashier-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $shiftLead = User::query()->updateOrCreate([
            'email' => 'shiftlead@duka.app',
        ], [
            'name' => 'Shift Lead',
            'password' => 'duka-shiftlead-password',
            'pin' => '1234',
            'role' => 'cashier',
        ]);

        return [
            'admin' => $admin,
            'manager' => $manager,
            'cashiers' => [$frontCounter, $shiftLead],
        ];
    }

    private function seedProducts(array $taxCategories)
    {
        $now = now();

        $catalog = [
            ['name' => 'Kabras Sugar 2kg', 'sku' => 'KABRAS-2KG', 'barcode' => '6161100100011', 'tax' => 'zero', 'base_price' => 365.00, 'cost_price' => 302.00, 'stock_quantity' => 18, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => null],
            ['name' => 'Ajab Wheat Flour 2kg', 'sku' => 'AJAB-2KG', 'barcode' => '6161100100012', 'tax' => 'zero', 'base_price' => 248.00, 'cost_price' => 206.00, 'stock_quantity' => 16, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => null],
            ['name' => 'Menengai Cream 500ml', 'sku' => 'MENENGAI-500ML', 'barcode' => '6161100100013', 'tax' => 'standard', 'base_price' => 198.00, 'cost_price' => 142.00, 'stock_quantity' => 24, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(8), 'batch_expiry_date' => $now->copy()->addDays(45)],
            ['name' => 'Fresh Fri 3L', 'sku' => 'FRESH-FRI-3L', 'barcode' => '6161100100014', 'tax' => 'standard', 'base_price' => 965.00, 'cost_price' => 812.00, 'stock_quantity' => 12, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(6), 'batch_expiry_date' => $now->copy()->addMonths(6)],
            ['name' => 'Ketepa Tea Bags 100s', 'sku' => 'KETEPA-100S', 'barcode' => '6161100100015', 'tax' => 'zero', 'base_price' => 214.00, 'cost_price' => 168.00, 'stock_quantity' => 22, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(4), 'batch_expiry_date' => $now->copy()->addMonths(8)],
            ['name' => 'Blue Band Original 1kg', 'sku' => 'BLUE-BAND-1KG', 'barcode' => '6161100100016', 'tax' => 'standard', 'base_price' => 512.00, 'cost_price' => 421.00, 'stock_quantity' => 14, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(9), 'batch_expiry_date' => $now->copy()->addMonths(5)],
            ['name' => 'Kimbo Cooking Fat 1kg', 'sku' => 'KIMBO-1KG', 'barcode' => '6161100100017', 'tax' => 'standard', 'base_price' => 286.00, 'cost_price' => 232.00, 'stock_quantity' => 18, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(11), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Jogoo Maize Flour 2kg', 'sku' => 'JOGOO-2KG', 'barcode' => '6161100100018', 'tax' => 'zero', 'base_price' => 176.00, 'cost_price' => 146.00, 'stock_quantity' => 17, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => null],
            ['name' => 'Unga wa Dola 2kg', 'sku' => 'DOLA-2KG', 'barcode' => '6161100100019', 'tax' => 'zero', 'base_price' => 168.00, 'cost_price' => 138.00, 'stock_quantity' => 15, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(2), 'batch_expiry_date' => null],
            ['name' => 'Brookside Long Life Milk 500ml', 'sku' => 'BROOKSIDE-500ML', 'barcode' => '6161100100020', 'tax' => 'standard', 'base_price' => 78.00, 'cost_price' => 71.00, 'stock_quantity' => 9, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(15), 'batch_expiry_date' => $now->copy()->addDay()],
            ['name' => 'Daima Yoghurt Strawberry 500ml', 'sku' => 'DAIMA-500ML', 'barcode' => '6161100100021', 'tax' => 'standard', 'base_price' => 132.00, 'cost_price' => 101.00, 'stock_quantity' => 11, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(7), 'batch_expiry_date' => $now->copy()->addDays(12)],
            ['name' => 'KCC Mala 500ml', 'sku' => 'KCC-MALA-500ML', 'barcode' => '6161100100022', 'tax' => 'standard', 'base_price' => 85.00, 'cost_price' => 74.00, 'stock_quantity' => 8, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(14), 'batch_expiry_date' => $now->copy()->addDays(2)],
            ['name' => 'Keringet Mineral Water 1.5L', 'sku' => 'KERINGET-1.5L', 'barcode' => '6161100100023', 'tax' => 'standard', 'base_price' => 75.00, 'cost_price' => 48.00, 'stock_quantity' => 34, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(6), 'batch_expiry_date' => $now->copy()->addMonths(9)],
            ['name' => 'Coca-Cola 500ml', 'sku' => 'COKE-500ML', 'barcode' => '6161100100024', 'tax' => 'standard', 'base_price' => 75.00, 'cost_price' => 51.00, 'stock_quantity' => 28, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Fanta Orange 500ml', 'sku' => 'FANTA-500ML', 'barcode' => '6161100100025', 'tax' => 'standard', 'base_price' => 75.00, 'cost_price' => 51.00, 'stock_quantity' => 26, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Sprite 500ml', 'sku' => 'SPRITE-500ML', 'barcode' => '6161100100026', 'tax' => 'standard', 'base_price' => 75.00, 'cost_price' => 51.00, 'stock_quantity' => 26, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Minute Maid Mango 1L', 'sku' => 'MM-MANGO-1L', 'barcode' => '6161100100027', 'tax' => 'standard', 'base_price' => 165.00, 'cost_price' => 118.00, 'stock_quantity' => 19, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(4), 'batch_expiry_date' => $now->copy()->addMonths(5)],
            ['name' => 'Dettol Soap 70g', 'sku' => 'DETTOL-70G', 'barcode' => '6161100100028', 'tax' => 'standard', 'base_price' => 92.00, 'cost_price' => 66.00, 'stock_quantity' => 21, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(18), 'batch_expiry_date' => null],
            ['name' => 'Sunlight Bar Soap 1kg', 'sku' => 'SUNLIGHT-1KG', 'barcode' => '6161100100029', 'tax' => 'standard', 'base_price' => 224.00, 'cost_price' => 178.00, 'stock_quantity' => 14, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(12), 'batch_expiry_date' => null],
            ['name' => 'Omo Detergent 1kg', 'sku' => 'OMO-1KG', 'barcode' => '6161100100030', 'tax' => 'standard', 'base_price' => 345.00, 'cost_price' => 286.00, 'stock_quantity' => 13, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(8), 'batch_expiry_date' => null],
            ['name' => 'Soko Tissue 10 Pack', 'sku' => 'SOKO-TISSUE-10', 'barcode' => '6161100100031', 'tax' => 'standard', 'base_price' => 148.00, 'cost_price' => 109.00, 'stock_quantity' => 17, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(10), 'batch_expiry_date' => null],
            ['name' => 'Colgate Maximum Cavity 100ml', 'sku' => 'COLGATE-100ML', 'barcode' => '6161100100032', 'tax' => 'standard', 'base_price' => 135.00, 'cost_price' => 99.00, 'stock_quantity' => 16, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(15), 'batch_expiry_date' => null],
            ['name' => 'Always Ultra Maxi 10s', 'sku' => 'ALWAYS-MAXI-10', 'barcode' => '6161100100033', 'tax' => 'standard', 'base_price' => 168.00, 'cost_price' => 126.00, 'stock_quantity' => 14, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(11), 'batch_expiry_date' => null],
            ['name' => 'Pampers Baby Dry S3', 'sku' => 'PAMPERS-S3', 'barcode' => '6161100100034', 'tax' => 'standard', 'base_price' => 825.00, 'cost_price' => 688.00, 'stock_quantity' => 9, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(9), 'batch_expiry_date' => null],
            ['name' => 'Weetabix Original 24s', 'sku' => 'WEETABIX-24S', 'barcode' => '6161100100035', 'tax' => 'zero', 'base_price' => 368.00, 'cost_price' => 299.00, 'stock_quantity' => 12, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(16), 'batch_expiry_date' => $now->copy()->addMonths(5)],
            ['name' => 'Eggs Tray 30 Pack', 'sku' => 'EGGS-TRAY-30', 'barcode' => '6161100100036', 'tax' => 'zero', 'base_price' => 495.00, 'cost_price' => 428.00, 'stock_quantity' => 10, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(1), 'batch_expiry_date' => $now->copy()->addDays(10)],
            ['name' => 'Plastic Bags Small', 'sku' => 'PLASTIC-BAGS-SMALL', 'barcode' => null, 'tax' => 'standard', 'base_price' => 5.00, 'cost_price' => 2.00, 'stock_quantity' => 160, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(2), 'batch_expiry_date' => null],
            ['name' => 'Loose Sugar', 'sku' => 'LOOSE-SUGAR', 'barcode' => null, 'tax' => 'zero', 'base_price' => 92.00, 'cost_price' => 74.00, 'stock_quantity' => 18, 'allow_fractional_sales' => true, 'last_received_date' => $now->copy()->subDays(4), 'batch_expiry_date' => null],
            ['name' => 'Kerosene', 'sku' => 'KEROSENE', 'barcode' => null, 'tax' => 'fuel', 'base_price' => 184.00, 'cost_price' => 162.00, 'stock_quantity' => 12, 'allow_fractional_sales' => true, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => null],
            ['name' => 'Taifa Matches 10 Pack', 'sku' => 'TAIFA-MATCHES-10', 'barcode' => '6161100100039', 'tax' => 'standard', 'base_price' => 62.00, 'cost_price' => 41.00, 'stock_quantity' => 31, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => null],
            ['name' => 'Bahari Iodised Salt 1kg', 'sku' => 'BAHARI-SALT-1KG', 'barcode' => '6161100100040', 'tax' => 'zero', 'base_price' => 74.00, 'cost_price' => 53.00, 'stock_quantity' => 25, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(3), 'batch_expiry_date' => $now->copy()->addMonths(12)],
            ['name' => 'Ndume Beans 1kg', 'sku' => 'NDUME-BEANS-1KG', 'barcode' => '6161100100041', 'tax' => 'zero', 'base_price' => 198.00, 'cost_price' => 151.00, 'stock_quantity' => 9, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => $now->copy()->addMonths(9)],
            ['name' => 'Pishori Rice 2kg', 'sku' => 'PISHORI-2KG', 'barcode' => '6161100100042', 'tax' => 'zero', 'base_price' => 392.00, 'cost_price' => 321.00, 'stock_quantity' => 10, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(6), 'batch_expiry_date' => $now->copy()->addMonths(10)],
            ['name' => 'Rosy Toilet Cleaner 1L', 'sku' => 'ROSY-1L', 'barcode' => '6161100100043', 'tax' => 'standard', 'base_price' => 214.00, 'cost_price' => 162.00, 'stock_quantity' => 11, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(12), 'batch_expiry_date' => null],
            ['name' => 'Mumias Lemon Biscuits 300g', 'sku' => 'LEMON-BISCUITS-300G', 'barcode' => '6161100100044', 'tax' => 'zero', 'base_price' => 88.00, 'cost_price' => 63.00, 'stock_quantity' => 23, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(7), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Orbit Chewing Gum Peppermint', 'sku' => 'ORBIT-PEPPERMINT', 'barcode' => '6161100100045', 'tax' => 'standard', 'base_price' => 35.00, 'cost_price' => 21.00, 'stock_quantity' => 42, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => $now->copy()->addMonths(7)],
        ];

        return collect($catalog)->mapWithKeys(function (array $product) use ($taxCategories) {
            $record = Product::query()->updateOrCreate([
                'sku' => $product['sku'],
            ], [
                'name' => $product['name'],
                'sku' => $product['sku'],
                'barcode' => $product['barcode'],
                'tax_category_id' => $taxCategories[$product['tax']]->id,
                'base_price' => $product['base_price'],
                'cost_price' => $product['cost_price'],
                'stock_quantity' => $product['stock_quantity'],
                'allow_fractional_sales' => $product['allow_fractional_sales'],
                'price_tiers' => null,
                'is_composite' => false,
                'last_received_date' => $product['last_received_date']->toDateString(),
                'batch_expiry_date' => $product['batch_expiry_date']?->toDateString(),
            ]);

            return [$product['sku'] => $record->load('taxCategory')];
        });
    }

    private function seedCustomers()
    {
        $names = [
            'Amina Wanjiku',
            'Brian Otieno',
            'Caroline Njeri',
            'David Kiptoo',
            'Faith Atieno',
            'George Mutua',
            'Hellen Moraa',
            'Irene Jepchirchir',
            'John Mwangi',
            'Kevin Ochieng',
            'Lucy Wairimu',
            'Mercy Chebet',
        ];

        return collect($names)->map(function (string $name) {
            return Customer::query()->create([
                'name' => $name,
                'phone' => null,
                'trust_score' => 100,
                'loyalty_points_balance' => random_int(10, 800),
                'credit_limit' => random_int(0, 5000),
            ]);
        });
    }

    private function resetOperationalData(): void
    {
        Payment::query()->delete();
        SaleItem::query()->delete();
        Sale::query()->delete();
        IncomingMpesaPayment::query()->delete();
        Customer::query()->delete();
    }

    private function seedHistoricalSales($products, $customers, array $cashiers): void
    {
        $weightedSkus = [
            'KABRAS-2KG', 'KABRAS-2KG', 'KABRAS-2KG',
            'AJAB-2KG', 'AJAB-2KG',
            'JOGOO-2KG', 'JOGOO-2KG',
            'BROOKSIDE-500ML', 'BROOKSIDE-500ML', 'KCC-MALA-500ML',
            'FRESH-FRI-3L',
            'KETEPA-100S',
            'BLUE-BAND-1KG',
            'PLASTIC-BAGS-SMALL', 'PLASTIC-BAGS-SMALL', 'PLASTIC-BAGS-SMALL',
            'LOOSE-SUGAR', 'LOOSE-SUGAR',
            'KEROSENE', 'KEROSENE',
            'BAHARI-SALT-1KG',
            'NDUME-BEANS-1KG',
            'PISHORI-2KG',
            'COKE-500ML', 'FANTA-500ML', 'SPRITE-500ML',
            'KERINGET-1.5L',
            'TAIFA-MATCHES-10',
            'ORBIT-PEPPERMINT',
            'EGGS-TRAY-30',
        ];

        $peakHours = [7, 8, 8, 9, 10, 12, 12, 13, 13, 14, 17, 17, 18, 18, 19, 20];
        $saleCounter = 1;

        for ($dayOffset = 29; $dayOffset >= 0; $dayOffset--) {
            $salesForDay = 6 + (($dayOffset + 3) % 4);

            for ($index = 0; $index < $salesForDay; $index++) {
                $createdAt = now()
                    ->subDays($dayOffset)
                    ->setTime($peakHours[array_rand($peakHours)], random_int(0, 59), random_int(0, 59));

                $cashier = $cashiers[array_rand($cashiers)];
                $customer = random_int(1, 100) <= 60 ? $customers->random() : null;
                $lineCount = random_int(1, 4);
                $selectedSkus = [];
                $saleItems = [];
                $subtotal = 0.0;
                $taxTotal = 0.0;

                while (count($saleItems) < $lineCount) {
                    $sku = $weightedSkus[array_rand($weightedSkus)];

                    if (in_array($sku, $selectedSkus, true)) {
                        continue;
                    }

                    /** @var Product $product */
                    $product = $products[$sku];
                    $selectedSkus[] = $sku;
                    $quantity = $this->randomQuantityForProduct($product);
                    $unitPrice = $this->historicalUnitPrice($product, $createdAt);
                    $lineSubtotal = round($quantity * $unitPrice, 2);
                    $lineTax = round($lineSubtotal * (((float) $product->taxCategory->rate) / 100), 2);

                    $saleItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                        'created_at' => $createdAt,
                    ];

                    $subtotal += $lineSubtotal;
                    $taxTotal += $lineTax;
                }

                $subtotal = round($subtotal, 2);
                $taxTotal = round($taxTotal, 2);
                $expectedSubtotal = round(collect($saleItems)->sum(fn (array $item): float => round((float) $item['product']->base_price * $item['quantity'], 2)), 2);
                $discountTotal = max(0, round($expectedSubtotal - $subtotal, 2));
                $grandTotal = round($subtotal + $taxTotal, 2);

                $sale = Sale::query()->create([
                    'user_id' => $cashier->id,
                    'customer_id' => $customer?->id,
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'status' => 'completed',
                    'receipt_number' => sprintf('SIM-%s-%04d', $createdAt->format('Ymd'), $saleCounter),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($saleItems as $item) {
                    SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product']->id,
                        'item_name' => $item['product']->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                $method = random_int(1, 100) <= 58 ? 'mpesa' : 'cash';

                Payment::query()->create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'amount' => $grandTotal,
                    'reference_number' => $method === 'mpesa'
                        ? sprintf('QK%s%04d', $createdAt->format('mdHi'), $saleCounter)
                        : null,
                    'status' => 'completed',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $saleCounter++;
            }
        }
    }

    private function seedIncomingMpesaFeed(): void
    {
        $names = [
            'Naomi Kariuki',
            'Peter Maina',
            'Sheila Achieng',
            'Victor Mutiso',
            'Zippy Naliaka',
            'Kevin Barasa',
            'Mary Nyambura',
            'Dennis Kiplangat',
        ];

        foreach (range(1, 10) as $index) {
            $createdAt = now()->subMinutes($index * 11);
            $amounts = [120.00, 185.00, 240.00, 375.00, 510.00, 825.00];

            IncomingMpesaPayment::query()->create([
                'transaction_code' => 'RFL' . now()->format('dHi') . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'customer_name' => $names[array_rand($names)],
                'phone_number' => '2547' . random_int(10000000, 99999999),
                'amount' => $amounts[array_rand($amounts)],
                'status' => 'pending',
                'claimed_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function historicalUnitPrice(Product $product, Carbon $createdAt): float
    {
        $basePrice = round((float) $product->base_price, 2);
        $costPrice = round((float) $product->cost_price, 2);

        if (
            $product->batch_expiry_date !== null &&
            Carbon::parse($product->batch_expiry_date)->isFuture() &&
            Carbon::parse($product->batch_expiry_date)->lte($createdAt->copy()->addDays(2)->endOfDay())
        ) {
            return round(max($basePrice * 0.92, $costPrice * 1.04), 2);
        }

        if (in_array($product->sku, ['PLASTIC-BAGS-SMALL', 'LOOSE-SUGAR', 'KEROSENE'], true)) {
            return $basePrice;
        }

        if ((int) $createdAt->format('d') % 6 === 0) {
            return round(max($basePrice * 0.95, $costPrice * 1.08), 2);
        }

        return $basePrice;
    }

    private function randomQuantityForProduct(Product $product): float
    {
        if ($product->allow_fractional_sales) {
            $options = in_array($product->sku, ['LOOSE-SUGAR', 'KEROSENE'], true)
                ? [0.25, 0.50, 0.75, 1.00, 1.50, 2.00]
                : [0.25, 0.50, 1.00];

            return $options[array_rand($options)];
        }

        if (in_array($product->sku, ['PLASTIC-BAGS-SMALL', 'ORBIT-PEPPERMINT', 'TAIFA-MATCHES-10'], true)) {
            return (float) random_int(1, 6);
        }

        if (in_array($product->sku, ['BROOKSIDE-500ML', 'KCC-MALA-500ML', 'COKE-500ML', 'FANTA-500ML', 'SPRITE-500ML'], true)) {
            return (float) random_int(1, 4);
        }

        return (float) random_int(1, 3);
    }
}
