<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'name'], 'products_category_name_idx');
            $table->index(['stock_quantity', 'name'], 'products_stock_name_idx');
            $table->index('last_received_date', 'products_last_received_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'sales_status_created_idx');
            $table->index(['user_id', 'status', 'created_at'], 'sales_user_status_created_idx');
            $table->index(['status', 'updated_at'], 'sales_status_updated_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['sale_id', 'product_id'], 'sale_items_sale_product_idx');
            $table->index(['product_id', 'sale_id'], 'sale_items_product_sale_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['sale_id', 'status'], 'payments_sale_status_idx');
            $table->index(['method', 'status', 'reference_number'], 'payments_method_status_ref_idx');
            $table->index(['method', 'amount', 'phone_number_normalized', 'created_at'], 'payments_mpesa_dup_idx');
        });

        Schema::table('incoming_mpesa_payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'incoming_mpesa_status_created_idx');
            $table->index(['amount', 'phone_number_normalized', 'created_at'], 'incoming_mpesa_dup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_mpesa_payments', function (Blueprint $table) {
            $table->dropIndex('incoming_mpesa_dup_idx');
            $table->dropIndex('incoming_mpesa_status_created_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_mpesa_dup_idx');
            $table->dropIndex('payments_method_status_ref_idx');
            $table->dropIndex('payments_sale_status_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_product_sale_idx');
            $table->dropIndex('sale_items_sale_product_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_status_updated_idx');
            $table->dropIndex('sales_user_status_created_idx');
            $table->dropIndex('sales_status_created_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_last_received_idx');
            $table->dropIndex('products_stock_name_idx');
            $table->dropIndex('products_category_name_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_idx');
        });
    }
};
