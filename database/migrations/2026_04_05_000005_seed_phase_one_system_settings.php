<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'enable_credit_sales' => 'true',
            'enable_etims' => 'false',
            'enable_loyalty_points' => 'true',
            'enable_hardware_printer' => 'false',
            'enable_fractional_stock' => 'false',
            'enable_wholesale' => 'false',
            'enable_mututho_lock' => 'false',
            'is_app_configured' => 'false',
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    public function down(): void
    {
        SystemSetting::query()
            ->whereIn('key', [
                'enable_fractional_stock',
                'enable_wholesale',
                'enable_mututho_lock',
                'is_app_configured',
            ])
            ->delete();
    }
};
