<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class ReceiptPrinterService
{
    public function printSale(Sale $sale): void
    {
        $payload = $this->generateReceiptPayload($sale);

        Log::info('Receipt payload generated for hardware print queue.', [
            'sale_id' => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'payload_length' => strlen($payload),
        ]);
    }

    /**
     * Generate ESC/POS byte payload for a thermal printer and encode as base64.
     */
    public function generateReceiptPayload(Sale $sale): string
    {
        $commands = $this->buildEscPosCommands($sale);

        return base64_encode($commands);
    }

    private function buildEscPosCommands(Sale $sale): string
    {
        $esc = "\x1b";
        $gs = "\x1d";

        // Initialize printer
        $data = $esc . "@";

        // Center align
        $data .= $esc . "a" . "\x01";
        $data .= "DUKA-APP POS\n";
        $data .= "--------------------------------\n";
        
        // Left align
        $data .= $esc . "a" . "\x00";
        $data .= "Receipt: " . $sale->receipt_number . "\n";
        $data .= "Date: " . $sale->created_at->format('Y-m-d H:i') . "\n";
        $data .= "--------------------------------\n";

        foreach ($sale->saleItems as $item) {
            $name = str_pad(substr($item->item_name, 0, 20), 20);
            $qty = str_pad((string) $item->quantity, 4, ' ', STR_PAD_LEFT);
            $total = str_pad(number_format((float) $item->subtotal, 2), 8, ' ', STR_PAD_LEFT);
            $data .= "{$name} {$qty} {$total}\n";
        }

        $data .= "--------------------------------\n";
        $data .= "TOTAL: " . number_format((float) $sale->grand_total, 2) . "\n";
        $data .= "--------------------------------\n";
        $data .= "\n\n\n\n"; // Paper feed

        // Cut paper
        $data .= $gs . "V" . "\x41" . "\x03";

        return $data;
    }
}
