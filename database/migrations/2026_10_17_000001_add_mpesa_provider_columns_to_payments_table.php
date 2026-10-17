<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('reference_number');
            $table->string('provider_status_code')->nullable()->after('provider_reference');
            $table->string('provider_status_description')->nullable()->after('provider_status_code');
            $table->json('provider_payload')->nullable()->after('provider_status_description');
            $table->timestamp('provider_confirmed_at')->nullable()->after('provider_payload');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'provider_reference',
                'provider_status_code',
                'provider_status_description',
                'provider_payload',
                'provider_confirmed_at',
            ]);
        });
    }
};
