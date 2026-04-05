<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->where('key', 'enable_mututho_lock')
            ->update(['key' => 'enable_sales_hours_lock']);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'enable_sales_hours_lock')
            ->update(['key' => 'enable_mututho_lock']);
    }
};
