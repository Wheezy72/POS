<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->dateTime('opening_time');
            $table->dateTime('closing_time')->nullable();
            $table->decimal('opening_cash', 10, 2);
            $table->decimal('declared_closing_cash', 10, 2)->nullable();
            $table->decimal('expected_closing_cash', 10, 2)->nullable();
            $table->enum('status', ['open', 'closed']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_ledgers');
    }
};
