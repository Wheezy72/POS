<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncSaleToCloud implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function handle(): void
    {
        Log::info("Attempting to sync Sale ID {$this->sale->id} to cloud...");
    }
}
