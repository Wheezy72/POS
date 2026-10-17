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
            'pos_clock_warn_drift_seconds' => '30',
            'pos_clock_reject_drift_seconds' => '300',
            'pricing_expiry_markdown_hours' => '48',
            'pricing_expiry_markdown_percentage' => '50',
            'pricing_stock_age_mid_days' => '90',
            'pricing_stock_age_old_days' => '120',
            'pricing_margin_multiplier_fresh' => '1.15',
            'pricing_margin_multiplier_mid' => '1.05',
            'pricing_margin_multiplier_old' => '1.00',
            'enable_fractional_stock' => 'false',
            'enable_wholesale' => 'false',
            'enable_sales_hours_lock' => 'false',
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
                'enable_sales_hours_lock',
                'is_app_configured',
            ])
            ->delete();
    }
};
