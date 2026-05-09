<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\IncomingMpesaPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemSetting;
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
            $this->seedSystemSettings();
            $taxCategories = $this->seedTaxCategories();
            $categories = $this->seedCategories();
            $users = $this->seedUsers();
            $products = $this->seedProducts($taxCategories, $categories);

            $this->resetOperationalData();
            $customers = $this->seedCustomers();
            $this->seedHistoricalSales($products, $customers, $users['cashiers']);
            $this->seedIncomingMpesaFeed();
        });
    }

    private function seedSystemSettings(): void
    {
        foreach ([
            'enable_credit_sales' => true,
            'enable_etims' => false,
            'enable_loyalty_points' => true,
            'enable_hardware_printer' => false,
            'enable_fractional_stock' => false,
            'enable_wholesale' => false,
            'enable_sales_hours_lock' => false,
            'is_app_configured' => false,
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value ? 'true' : 'false'],
            );
        }
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
            'pin' => '1234',
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
            'pin' => '1357',
            'role' => 'cashier',
        ]);

        return [
            'admin' => $admin,
            'manager' => $manager,
            'cashiers' => [$frontCounter, $shiftLead],
        ];
    }

    private function seedCategories(): array
    {
        $catalog = [
            ['name' => 'Staples & Flours', 'slug' => 'staples-flours', 'description' => 'Maize flour, sugar, rice, beans, and other pantry staples.'],
            ['name' => 'Dairy & Chilled', 'slug' => 'dairy-chilled', 'description' => 'Milk, mala, yoghurt, and chilled essentials.'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Soft drinks, juices, water, tea, and energy drinks.'],
            ['name' => 'Cooking Essentials', 'slug' => 'cooking-essentials', 'description' => 'Oils, fats, and seasoning essentials.'],
            ['name' => 'Household', 'slug' => 'household', 'description' => 'Cleaning supplies, tissues, and general household goods.'],
            ['name' => 'Personal Care', 'slug' => 'personal-care', 'description' => 'Soap, toothpaste, tissue, and hygiene products.'],
            ['name' => 'Baby Care', 'slug' => 'baby-care', 'description' => 'Baby diapers, wipes, and child essentials.'],
            ['name' => 'Snacks & Biscuits', 'slug' => 'snacks-biscuits', 'description' => 'Biscuits, gum, bread, and quick snacks.'],
        ];

        return collect($catalog)->mapWithKeys(function (array $category) {
            $record = \App\Models\Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );

            return [$category['slug'] => $record];
        })->all();
    }

    private function seedProducts(array $taxCategories, array $categories)
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
            ['name' => 'KCC Fresh Milk 1L', 'sku' => 'KCC-FRESH-1L', 'barcode' => '6161100100046', 'tax' => 'standard', 'base_price' => 128.00, 'cost_price' => 109.00, 'stock_quantity' => 14, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(2), 'batch_expiry_date' => $now->copy()->addDays(5)],
            ['name' => 'Brookside Mala 1L', 'sku' => 'BROOKSIDE-MALA-1L', 'barcode' => '6161100100047', 'tax' => 'standard', 'base_price' => 145.00, 'cost_price' => 119.00, 'stock_quantity' => 12, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(2), 'batch_expiry_date' => $now->copy()->addDays(7)],
            ['name' => 'Minute Maid Apple 1L', 'sku' => 'MM-APPLE-1L', 'barcode' => '6161100100048', 'tax' => 'standard', 'base_price' => 165.00, 'cost_price' => 118.00, 'stock_quantity' => 17, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => $now->copy()->addMonths(5)],
            ['name' => 'Power Play Energy Drink 300ml', 'sku' => 'POWER-PLAY-300ML', 'barcode' => '6161100100049', 'tax' => 'standard', 'base_price' => 95.00, 'cost_price' => 72.00, 'stock_quantity' => 21, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(6), 'batch_expiry_date' => $now->copy()->addMonths(4)],
            ['name' => 'Kane Extra 350ml', 'sku' => 'KANE-EXTRA-350ML', 'barcode' => '6161100100050', 'tax' => 'standard', 'base_price' => 62.00, 'cost_price' => 46.00, 'stock_quantity' => 32, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(4), 'batch_expiry_date' => $now->copy()->addMonths(3)],
            ['name' => 'Supa Loaf Bread 400g', 'sku' => 'SUPA-LOAF-400G', 'barcode' => '6161100100051', 'tax' => 'zero', 'base_price' => 72.00, 'cost_price' => 56.00, 'stock_quantity' => 18, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDay(), 'batch_expiry_date' => $now->copy()->addDays(4)],
            ['name' => 'Festive White Bread 600g', 'sku' => 'FESTIVE-600G', 'barcode' => '6161100100052', 'tax' => 'zero', 'base_price' => 84.00, 'cost_price' => 63.00, 'stock_quantity' => 16, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDay(), 'batch_expiry_date' => $now->copy()->addDays(3)],
            ['name' => 'Golden Fry 2L', 'sku' => 'GOLDEN-FRY-2L', 'barcode' => '6161100100053', 'tax' => 'standard', 'base_price' => 648.00, 'cost_price' => 548.00, 'stock_quantity' => 13, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(8), 'batch_expiry_date' => $now->copy()->addMonths(6)],
            ['name' => 'Royco Mchuzi Mix 24 Cubes', 'sku' => 'ROYCO-24', 'barcode' => '6161100100054', 'tax' => 'zero', 'base_price' => 128.00, 'cost_price' => 94.00, 'stock_quantity' => 29, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(10), 'batch_expiry_date' => $now->copy()->addMonths(9)],
            ['name' => 'Indomie Chicken Noodles 5 Pack', 'sku' => 'INDOMIE-5PK', 'barcode' => '6161100100055', 'tax' => 'zero', 'base_price' => 118.00, 'cost_price' => 82.00, 'stock_quantity' => 26, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(7), 'batch_expiry_date' => $now->copy()->addMonths(6)],
            ['name' => 'Bidco White Star 2kg', 'sku' => 'WHITE-STAR-2KG', 'barcode' => '6161100100056', 'tax' => 'zero', 'base_price' => 182.00, 'cost_price' => 151.00, 'stock_quantity' => 15, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(5), 'batch_expiry_date' => null],
            ['name' => 'Nescafe Classic 100g', 'sku' => 'NESCAFE-100G', 'barcode' => '6161100100057', 'tax' => 'standard', 'base_price' => 485.00, 'cost_price' => 401.00, 'stock_quantity' => 8, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(14), 'batch_expiry_date' => $now->copy()->addMonths(8)],
            ['name' => 'Kenyas Choice Tea Masala 250g', 'sku' => 'TEA-MASALA-250G', 'barcode' => '6161100100058', 'tax' => 'zero', 'base_price' => 102.00, 'cost_price' => 76.00, 'stock_quantity' => 19, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(9), 'batch_expiry_date' => $now->copy()->addMonths(10)],
            ['name' => 'Softcare Baby Wipes 80s', 'sku' => 'SOFTCARE-WIPES-80', 'barcode' => '6161100100059', 'tax' => 'standard', 'base_price' => 205.00, 'cost_price' => 154.00, 'stock_quantity' => 11, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(6), 'batch_expiry_date' => null],
            ['name' => 'Huggies Dry Comfort S4', 'sku' => 'HUGGIES-S4', 'barcode' => '6161100100060', 'tax' => 'standard', 'base_price' => 1085.00, 'cost_price' => 925.00, 'stock_quantity' => 7, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(10), 'batch_expiry_date' => null],
            ['name' => 'Nice & Lovely Petroleum Jelly 250ml', 'sku' => 'PETROLEUM-JELLY-250', 'barcode' => '6161100100061', 'tax' => 'standard', 'base_price' => 128.00, 'cost_price' => 91.00, 'stock_quantity' => 14, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(12), 'batch_expiry_date' => null],
            ['name' => 'Vaseline Cocoa Glow 400ml', 'sku' => 'VAS-COCOA-400', 'barcode' => '6161100100062', 'tax' => 'standard', 'base_price' => 455.00, 'cost_price' => 362.00, 'stock_quantity' => 9, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(16), 'batch_expiry_date' => null],
            ['name' => 'Bic Shavers 5 Pack', 'sku' => 'BIC-SHAVERS-5', 'barcode' => '6161100100063', 'tax' => 'standard', 'base_price' => 148.00, 'cost_price' => 101.00, 'stock_quantity' => 18, 'allow_fractional_sales' => false, 'last_received_date' => $now->copy()->subDays(8), 'batch_expiry_date' => null],
        ];

        foreach ($this->additionalMinimartProducts($now, 202 - count($catalog)) as $product) {
            $catalog[] = $product;
        }

        $categoryAssignments = [
            'KABRAS-2KG' => 'staples-flours',
            'AJAB-2KG' => 'staples-flours',
            'MENENGAI-500ML' => 'cooking-essentials',
            'FRESH-FRI-3L' => 'cooking-essentials',
            'KETEPA-100S' => 'beverages',
            'BLUE-BAND-1KG' => 'cooking-essentials',
            'KIMBO-1KG' => 'cooking-essentials',
            'JOGOO-2KG' => 'staples-flours',
            'DOLA-2KG' => 'staples-flours',
            'BROOKSIDE-500ML' => 'dairy-chilled',
            'DAIMA-500ML' => 'dairy-chilled',
            'KCC-MALA-500ML' => 'dairy-chilled',
            'KERINGET-1.5L' => 'beverages',
            'COKE-500ML' => 'beverages',
            'FANTA-500ML' => 'beverages',
            'SPRITE-500ML' => 'beverages',
            'MM-MANGO-1L' => 'beverages',
            'DETTOL-70G' => 'personal-care',
            'SUNLIGHT-1KG' => 'household',
            'OMO-1KG' => 'household',
            'SOKO-TISSUE-10' => 'personal-care',
            'COLGATE-100ML' => 'personal-care',
            'ALWAYS-MAXI-10' => 'personal-care',
            'PAMPERS-S3' => 'baby-care',
            'WEETABIX-24S' => 'snacks-biscuits',
            'EGGS-TRAY-30' => 'staples-flours',
            'PLASTIC-BAGS-SMALL' => 'household',
            'LOOSE-SUGAR' => 'staples-flours',
            'KEROSENE' => 'household',
            'TAIFA-MATCHES-10' => 'household',
            'BAHARI-SALT-1KG' => 'staples-flours',
            'NDUME-BEANS-1KG' => 'staples-flours',
            'PISHORI-2KG' => 'staples-flours',
            'ROSY-1L' => 'household',
            'LEMON-BISCUITS-300G' => 'snacks-biscuits',
            'ORBIT-PEPPERMINT' => 'snacks-biscuits',
            'KCC-FRESH-1L' => 'dairy-chilled',
            'BROOKSIDE-MALA-1L' => 'dairy-chilled',
            'MM-APPLE-1L' => 'beverages',
            'POWER-PLAY-300ML' => 'beverages',
            'KANE-EXTRA-350ML' => 'beverages',
            'SUPA-LOAF-400G' => 'snacks-biscuits',
            'FESTIVE-600G' => 'snacks-biscuits',
            'GOLDEN-FRY-2L' => 'cooking-essentials',
            'ROYCO-24' => 'cooking-essentials',
            'INDOMIE-5PK' => 'snacks-biscuits',
            'WHITE-STAR-2KG' => 'staples-flours',
            'NESCAFE-100G' => 'beverages',
            'TEA-MASALA-250G' => 'beverages',
            'SOFTCARE-WIPES-80' => 'baby-care',
            'HUGGIES-S4' => 'baby-care',
            'PETROLEUM-JELLY-250' => 'personal-care',
            'VAS-COCOA-400' => 'personal-care',
            'BIC-SHAVERS-5' => 'personal-care',
        ];

        return collect($catalog)->mapWithKeys(function (array $product) use ($taxCategories, $categories, $categoryAssignments) {
            $record = Product::query()->updateOrCreate([
                'sku' => $product['sku'],
            ], [
                'name' => $product['name'],
                'sku' => $product['sku'],
                'barcode' => $product['barcode'],
                'category_id' => $categories[$product['category'] ?? $categoryAssignments[$product['sku']] ?? 'household']->id,
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

    private function additionalMinimartProducts(Carbon $now, int $needed): array
    {
        if ($needed <= 0) {
            return [];
        }

        $groups = [
            ['category' => 'staples-flours', 'tax' => 'zero', 'items' => [
                ['Hostess Maize Flour 2kg', 174, 142], ['Exe Wheat Flour 2kg', 252, 211], ['Soko Maize Meal 2kg', 169, 137], ['Famila Porridge 1kg', 195, 146], ['Blue Triangle Flour 2kg', 181, 148], ['Pearl Rice 1kg', 188, 143], ['Cil Basmati Rice 1kg', 245, 191], ['Green Grams 1kg', 226, 174], ['Rosecoco Beans 1kg', 215, 166], ['Ndengu Split 500g', 118, 86], ['Popcorn Kernels 500g', 96, 68], ['Njahe Black Beans 1kg', 238, 181], ['Millet Flour 1kg', 142, 104], ['Sorghum Flour 1kg', 136, 99], ['Atta Mark 1 Flour 2kg', 259, 211], ['Tropikal Rice 2kg', 374, 303], ['Butterfly Rice 1kg', 172, 129], ['Brown Chapati Flour 2kg', 268, 214], ['Loose Rice Pishori 1kg', 196, 151], ['Loose Beans 1kg', 205, 154], ['Green Peas 500g', 132, 96], ['Kamande Lentils 500g', 148, 109], ['Oats 500g', 185, 139], ['Corn Starch 400g', 92, 63], ['Breadcrumbs 500g', 118, 82], ['Spaghetti 500g', 126, 91], ['Pasta Shells 500g', 124, 89], ['Macaroni 500g', 122, 87], ['Lasagna Sheets 250g', 238, 181], ['Semolina 1kg', 174, 132],
            ]],
            ['category' => 'dairy-chilled', 'tax' => 'standard', 'items' => [
                ['Tuzo Long Life Milk 500ml', 76, 63], ['Tuzo Fresh Milk 1L', 130, 109], ['Ilara Yoghurt Vanilla 500ml', 128, 98], ['Bio Yoghurt Natural 450ml', 156, 121], ['KCC Butter 250g', 365, 302], ['KCC Cheese Slices 200g', 298, 241], ['Brookside Fresh Milk 500ml', 72, 59], ['Daima Mala 500ml', 82, 68], ['Molo Milk 500ml', 74, 61], ['Lato Milk 500ml', 70, 56], ['Tuzo Mala 500ml', 84, 69], ['Delamere Yoghurt 450ml', 175, 137], ['Ilara Fresh Milk 500ml', 68, 55], ['KCC Ghee 500g', 690, 581], ['Brookside Butter 250g', 378, 315], ['Yoghurt Cup Strawberry 150ml', 55, 38], ['Yoghurt Cup Vanilla 150ml', 55, 38], ['Cream Cheese 250g', 420, 342],
            ]],
            ['category' => 'beverages', 'tax' => 'standard', 'items' => [
                ['Pepsi 500ml', 70, 48], ['Mountain Dew 500ml', 70, 48], ['Novida 500ml', 75, 51], ['Stoney Tangawizi 500ml', 75, 52], ['Dasani Water 1L', 65, 42], ['Aquamist Water 500ml', 35, 20], ['Highlands Water 1.5L', 72, 45], ['Afia Mango 1L', 155, 113], ['Afia Mixed Fruit 1L', 155, 113], ['Ribena Blackcurrant 1L', 365, 292], ['Quencher Juice 1L', 148, 106], ['Red Bull 250ml', 275, 222], ['Predator Energy 400ml', 95, 68], ['Monster Energy 500ml', 285, 226], ['Ketepa Tea Leaves 250g', 165, 125], ['Kericho Gold Tea 100s', 245, 191], ['Dormans Coffee 250g', 525, 421], ['Fahari Ya Kenya Tea 500g', 265, 204], ['Cadbury Drinking Chocolate 500g', 495, 393], ['Milo 400g', 568, 462], ['Tang Orange 125g', 88, 62], ['Ribena 250ml', 95, 68], ['Minute Maid Tropical 400ml', 82, 58], ['Del Monte Pineapple 1L', 235, 181], ['Pick N Peel Mango 1L', 178, 132], ['Kenylon Lemonade 2L', 145, 97], ['Coke 2L', 215, 159], ['Fanta 2L', 215, 159], ['Sprite 2L', 215, 159],
            ]],
            ['category' => 'cooking-essentials', 'tax' => 'standard', 'items' => [
                ['Elianto Sunflower Oil 1L', 365, 304], ['Elianto Sunflower Oil 2L', 715, 612], ['Fresh Fri 1L', 342, 286], ['Rina Vegetable Oil 1L', 335, 278], ['Rina Vegetable Oil 2L', 655, 558], ['Chipsy Cooking Oil 1L', 318, 259], ['Cowboy Cooking Fat 1kg', 295, 241], ['Royco Beef Cubes 20s', 110, 76], ['Royco Chicken Cubes 20s', 110, 76], ['Knorr Beef Cubes 20s', 118, 82], ['Tomato Paste 70g', 45, 29], ['Peptang Tomato Sauce 400g', 150, 109], ['Zesta Jam 500g', 248, 191], ['Blue Band 500g', 286, 232], ['Prestige Margarine 500g', 265, 214], ['Cumin Powder 100g', 98, 67], ['Pilau Masala 100g', 95, 64], ['Curry Powder 100g', 88, 58], ['Black Pepper 50g', 118, 82], ['Garlic Powder 50g', 105, 72], ['Soy Sauce 250ml', 165, 121], ['Vinegar 500ml', 95, 62], ['Baking Powder 100g', 65, 41], ['Yeast 50g', 78, 52],
            ]],
            ['category' => 'household', 'tax' => 'standard', 'items' => [
                ['Ariel Detergent 500g', 245, 195], ['Toss Detergent 500g', 172, 129], ['Sunlight Detergent 500g', 188, 143], ['Kleesoft Detergent 1kg', 238, 181], ['Jik Lemon 750ml', 175, 132], ['Jik Regular 750ml', 175, 132], ['Harpic Toilet Cleaner 500ml', 245, 194], ['Domestos 750ml', 238, 188], ['Velvex Kitchen Towels 2s', 185, 132], ['Hanan Tissue 10 Pack', 155, 116], ['Rosy Tissue 10 Pack', 148, 110], ['Scotch Brite Sponge 3 Pack', 135, 92], ['Steel Wool 12 Pack', 95, 61], ['Dishwashing Liquid 750ml', 168, 121], ['Morning Fresh 400ml', 165, 118], ['Air Freshener Lavender 300ml', 220, 162], ['Mortein Doom 400ml', 345, 278], ['Raid Insect Killer 300ml', 335, 265], ['Trash Bags Medium 20s', 210, 151], ['Aluminium Foil 10m', 185, 132], ['Cling Film 30m', 175, 128], ['Toothpicks 1000s', 55, 31], ['Serviettes 100s', 95, 61], ['Shoe Polish Black 40ml', 85, 54], ['Shoe Polish Brown 40ml', 85, 54], ['Mop Head Cotton', 185, 132], ['Broom Soft Indoor', 220, 159], ['Dustpan Set', 260, 190], ['Peg Pack 24s', 95, 62], ['Candles 8 Pack', 118, 82],
            ]],
            ['category' => 'personal-care', 'tax' => 'standard', 'items' => [
                ['Geisha Soap 90g', 78, 52], ['Imperial Leather Soap 100g', 92, 64], ['Lifebuoy Soap 90g', 86, 58], ['Dettol Handwash 250ml', 245, 186], ['Nivea Lotion 400ml', 545, 432], ['Garnier Lotion 400ml', 495, 392], ['Colgate Herbal 100ml', 138, 99], ['Sensodyne 75ml', 345, 275], ['Aquafresh 100ml', 128, 91], ['Closeup Red Hot 100ml', 125, 88], ['Nice & Lovely Hair Food 250g', 185, 139], ['Venus Hair Oil 250ml', 175, 132], ['Always Cotton Soft 8s', 138, 102], ['Kotex Normal 8s', 148, 109], ['Cotton Wool 100g', 85, 54], ['Ear Buds 100s', 75, 48], ['Rexona Roll On 50ml', 245, 188], ['Nivea Roll On 50ml', 265, 206], ['Vaseline Lip Therapy', 185, 132], ['Hand Sanitizer 100ml', 95, 61], ['Surgical Mask 10s', 120, 78], ['Toothbrush Medium', 75, 44],
            ]],
            ['category' => 'baby-care', 'tax' => 'standard', 'items' => [
                ['Pampers Premium S2', 785, 653], ['Pampers Pants S5', 1120, 945], ['Huggies Wipes 56s', 185, 138], ['Softcare Diapers S3', 680, 560], ['Softcare Diapers S4', 720, 598], ['Baby Petroleum Jelly 250ml', 135, 96], ['Baby Powder 100g', 145, 102], ['Cerelac Wheat 400g', 485, 392], ['Nan Infant Formula 400g', 1180, 1015], ['Lactogen 400g', 1050, 898], ['Baby Bottle 250ml', 220, 156], ['Pacifier 2 Pack', 185, 132],
            ]],
            ['category' => 'snacks-biscuits', 'tax' => 'zero', 'items' => [
                ['Britania Digestive 250g', 125, 88], ['Manji Digestive 200g', 108, 74], ['Oreo Original 154g', 165, 121], ['Bakers Inn Cookies 300g', 145, 106], ['Tropical Heat Crisps 50g', 65, 42], ['Krackles Crisps 50g', 60, 39], ['Chevda 200g', 145, 101], ['Peanuts Salted 200g', 155, 109], ['Cashew Nuts 100g', 285, 218], ['Popcorn Pack 100g', 55, 32], ['Blueberry Muffin', 65, 38], ['Queen Cake 6 Pack', 180, 126], ['Mcvities Shortbread 200g', 185, 139], ['Tamu Tamu Lollipop 50s', 235, 169], ['PK Chewing Gum', 20, 11], ['Big G Gum', 15, 8], ['Chocolate Bar 40g', 95, 61], ['Dairy Milk 80g', 185, 132], ['Mandazi Pack 4s', 80, 45], ['Samosa Beef', 60, 36], ['Sausage Roll', 85, 52], ['Fruit Cake 400g', 285, 205],
            ]],
        ];

        $products = [];
        $barcodeCounter = 6161100200000;

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if (count($products) >= $needed) {
                    break 2;
                }

                [$name, $basePrice, $costPrice] = $item;
                $sku = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
                $sku = trim($sku, '-');

                $products[] = [
                    'name' => $name,
                    'sku' => $sku,
                    'barcode' => (string) $barcodeCounter++,
                    'category' => $group['category'],
                    'tax' => $group['tax'],
                    'base_price' => (float) $basePrice,
                    'cost_price' => (float) $costPrice,
                    'stock_quantity' => random_int(8, 60),
                    'allow_fractional_sales' => false,
                    'last_received_date' => $now->copy()->subDays(random_int(1, 45)),
                    'batch_expiry_date' => in_array($group['category'], ['dairy-chilled', 'snacks-biscuits', 'beverages', 'staples-flours', 'cooking-essentials'], true)
                        ? $now->copy()->addDays(random_int(14, 360))
                        : null,
                ];
            }
        }

        return $products;
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
            $phone = '2547' . random_int(10000000, 99999999);

            return Customer::query()->create([
                'name' => $name,
                'phone' => $phone,
                'phone_normalized' => Customer::normalizePhone($phone),
                'trust_score' => 100,
                'loyalty_points_balance' => random_int(10, 800),
                'credit_limit' => random_int(1000, 8000),
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
        $allSkus = array_values($products->keys()->all());
        $weightedSkus = [];

        foreach ($allSkus as $index => $sku) {
            $product = $products[$sku];
            $weight = 1;

            if ((float) $product->base_price <= 150) {
                $weight += 2;
            }

            if (str_contains($sku, 'MILK') || str_contains($sku, 'BREAD') || str_contains($sku, 'COKE') || str_contains($sku, 'FANTA') || str_contains($sku, 'SUGAR') || str_contains($sku, 'FLOUR')) {
                $weight += 3;
            }

            if ($index < 60) {
                $weight += 1;
            }

            foreach (range(1, $weight) as $_) {
                $weightedSkus[] = $sku;
            }
        }

        $peakHours = [7, 8, 8, 9, 10, 11, 12, 12, 13, 13, 14, 16, 17, 17, 18, 18, 19, 20, 20];
        $quietHours = [6, 10, 11, 15, 16, 21];
        $saleCounter = 1;

        for ($dayOffset = 179; $dayOffset >= 0; $dayOffset--) {
            $day = now()->subDays($dayOffset)->startOfDay();
            $dayOfWeek = (int) $day->format('N');
            $dayOfMonth = (int) $day->format('j');
            $monthWave = sin(($dayOffset / 180) * pi() * 3);
            $weekendBoost = $dayOfWeek >= 6 ? 8 : 0;
            $paydayBoost = ($dayOfMonth >= 25 || $dayOfMonth <= 3) ? 7 : 0;
            $midMonthDip = ($dayOfMonth >= 12 && $dayOfMonth <= 19) ? -5 : 0;
            $salesForDay = max(7, (int) round(16 + $weekendBoost + $paydayBoost + $midMonthDip + ($monthWave * 4) + random_int(-4, 5)));

            for ($index = 0; $index < $salesForDay; $index++) {
                $hourPool = random_int(1, 100) <= 78 ? $peakHours : $quietHours;
                $createdAt = $day->copy()->setTime($hourPool[array_rand($hourPool)], random_int(0, 59), random_int(0, 59));
                $cashier = $cashiers[array_rand($cashiers)];
                $customer = random_int(1, 100) <= 46 ? $customers->random() : null;
                $lineCount = random_int(1, random_int(2, 7));
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
                    'receipt_number' => sprintf('SIM-%s-%05d', $createdAt->format('Ymd'), $saleCounter),
                    'client_checkout_id' => sprintf('seed-%s-%05d', $createdAt->format('Ymd'), $saleCounter),
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

                $methodRoll = random_int(1, 100);
                $method = $methodRoll <= 62 ? 'mpesa' : ($methodRoll <= 94 ? 'cash' : 'card');

                Payment::query()->create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'amount' => $grandTotal,
                    'reference_number' => $method === 'mpesa'
                        ? sprintf('QK%s%05d', $createdAt->format('mdHi'), $saleCounter)
                        : ($method === 'card' ? sprintf('CARD%s%05d', $createdAt->format('md'), $saleCounter) : null),
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
