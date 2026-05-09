<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncSaleToCloud implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [10, 60, 300, 600, 1200]; // Exponential backoff: 10s, 1m, 5m, 10m, 20m
    }

    public function __construct(public Sale $sale)
    {
    }

    public function handle(): void
    {
        Log::info("Attempting to sync Sale ID {$this->sale->id} to cloud...");
        
        // Simulating sync logic...
        // throw new \Exception("Cloud API Unreachable");
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\DB::table('failed_sync_payloads')->insert([
            'job_type' => self::class,
            'record_id' => $this->sale->id,
            'payload' => json_encode($this->sale->toArray()),
            'exception' => $exception->getMessage(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::error("Permanent sync failure for Sale ID {$this->sale->id}. Routed to dead-letter table.");
    }
}
