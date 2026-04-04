<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA journal_mode=WAL;');
    }

    public function down(): void
    {
        DB::statement('PRAGMA journal_mode=DELETE;');
    }
};
