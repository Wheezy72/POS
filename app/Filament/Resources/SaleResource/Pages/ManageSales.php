<?php

declare(strict_types=1);

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSales extends ManageRecords
{
    protected static string $resource = SaleResource::class;

    protected function canCreate(): bool
    {
        return false;
    }
}
