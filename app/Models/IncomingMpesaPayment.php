<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IncomingMpesaPayment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'phone_number' => 'encrypted',
            'amount' => 'decimal:2',
            'claimed_at' => 'datetime',
        ];
    }
}
