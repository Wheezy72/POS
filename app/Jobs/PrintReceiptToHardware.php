<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Services\ReceiptPrinterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PrintReceiptToHardware implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function handle(ReceiptPrinterService $receiptPrinterService): void
    {
        try {
            $sale = $this->sale->fresh([
                'customer',
                'saleItems.product.taxCategory',
                'payments',
                'user',
            ]);

            if ($sale === null) {
                return;
            }

            $receiptPrinterService->printSale($sale);
        } catch (Throwable $exception) {
            report($exception);

            AuditLog::query()->create([
                'user_id' => $this->sale->user_id,
                'action' => 'receipt_print_failed',
                'description' => "Receipt printing failed for sale {$this->sale->receipt_number}: {$exception->getMessage()}",
                'reference_id' => $this->sale->id,
            ]);
        }
    }
}
