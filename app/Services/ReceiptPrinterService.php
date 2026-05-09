<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class ReceiptPrinterService
{
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

        foreach ($sale->items as $item) {
            $name = str_pad(substr($item->product->name, 0, 20), 20);
            $qty = str_pad((string)$item->quantity, 4, ' ', STR_PAD_LEFT);
            $total = str_pad(number_format($item->total_price, 2), 8, ' ', STR_PAD_LEFT);
            $data .= "{$name} {$qty} {$total}\n";
        }

        $data .= "--------------------------------\n";
        $data .= "TOTAL: " . number_format($sale->total_amount, 2) . "\n";
        $data .= "--------------------------------\n";
        $data .= "\n\n\n\n"; // Paper feed

        // Cut paper
        $data .= $gs . "V" . "\x41" . "\x03";

        return $data;
    }
}
