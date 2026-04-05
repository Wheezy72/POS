<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\ReceiptPrinterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrintReceiptToHardware implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function handle(ReceiptPrinterService $receiptPrinterService): void
    {
        $receiptPrinterService->printSale(
            $this->sale->fresh([
                'customer',
                'saleItems.product.taxCategory',
                'payments',
                'user',
            ])
        );
    }
}
