<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('printer_type'); // e.g., 'usb', 'network'
            $table->string('printer_address'); // e.g., 'COM3', '192.168.1.100'
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('terminal_id')->nullable()->after('cashier_id');
            $table->foreign('terminal_id')->references('id')->on('terminals');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['terminal_id']);
            $table->dropColumn('terminal_id');
        });
        Schema::dropIfExists('terminals');
    }
};
