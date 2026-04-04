<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->date('last_received_date')->nullable();
            $table->date('batch_expiry_date')->nullable();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
        });

        Schema::table('shift_ledgers', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable();
            $table->string('previous_hash')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['shop_id', 'previous_hash']);
        });

        Schema::table('shift_ledgers', function (Blueprint $table) {
            $table->dropColumn('shop_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('shop_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('shop_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'shop_id',
                'cost_price',
                'last_received_date',
                'batch_expiry_date',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shop_id');
        });
    }
};
