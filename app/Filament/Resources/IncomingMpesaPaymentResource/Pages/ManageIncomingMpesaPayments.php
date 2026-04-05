<?php

declare(strict_types=1);

namespace App\Filament\Resources\IncomingMpesaPaymentResource\Pages;

use App\Filament\Resources\IncomingMpesaPaymentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageIncomingMpesaPayments extends ManageRecords
{
    protected static string $resource = IncomingMpesaPaymentResource::class;

    protected function canCreate(): bool
    {
        return false;
    }
}
