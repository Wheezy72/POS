<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class ReceiptPrinterService
{
    public function printSale(?Sale $sale): void
    {
        if ($sale === null) {
            return;
        }

        $commands = $this->buildEscPosCommands($sale);

        // Day 2 scaffold only:
        // the queue/job boundary is in place so a future hardware adapter can
        // write these raw bytes directly to a USB or network ESC/POS printer.
        Log::info('ESC/POS receipt payload prepared.', [
            'sale_id' => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'hex' => bin2hex($commands),
        ]);
    }

    private function buildEscPosCommands(Sale $sale): string
    {
        $lines = [
            "\x1B\x40",
            "DUKA APP\r\n",
            "Receipt {$sale->receipt_number}\r\n",
            "Total KES " . number_format((float) $sale->grand_total, 2) . "\r\n",
            "Thank you\r\n",
            "\x1D\x56\x41\x03",
        ];

        return implode('', $lines);
    }
}
