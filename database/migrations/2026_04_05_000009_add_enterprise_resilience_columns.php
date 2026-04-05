<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_suspected_duplicate')->default(false)->after('status');
            $table->string('suspected_duplicate_reason')->nullable()->after('is_suspected_duplicate');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->text('phone_number')->nullable()->after('amount');
            $table->string('phone_number_normalized')->nullable()->after('phone_number');
        });

        Schema::table('incoming_mpesa_payments', function (Blueprint $table) {
            $table->string('phone_number_normalized')->nullable()->after('phone_number');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('integrity_hash')->nullable()->after('previous_hash');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('integrity_hash');
        });

        Schema::table('incoming_mpesa_payments', function (Blueprint $table) {
            $table->dropColumn('phone_number_normalized');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'phone_number_normalized',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'is_suspected_duplicate',
                'suspected_duplicate_reason',
            ]);
        });
    }
};
