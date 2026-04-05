<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_normalized')->nullable()->unique()->after('phone');
        });

        Customer::query()->each(function (Customer $customer): void {
            $phone = $customer->phone;

            if ($phone === null || $phone === '') {
                return;
            }

            $customer->forceFill([
                'phone_normalized' => Customer::normalizePhone($phone),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone_normalized']);
            $table->dropColumn('phone_normalized');
        });
    }
};
