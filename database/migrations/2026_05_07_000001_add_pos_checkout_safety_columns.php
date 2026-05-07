<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('client_checkout_id')->nullable()->after('receipt_number');
            $table->unique(['user_id', 'client_checkout_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('incoming_mpesa_payment_id')
                ->nullable()
                ->after('sale_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unique('incoming_mpesa_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['incoming_mpesa_payment_id']);
            $table->dropConstrainedForeignId('incoming_mpesa_payment_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'client_checkout_id']);
            $table->dropColumn('client_checkout_id');
        });
    }
};
