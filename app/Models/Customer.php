<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'credit_limit' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            $customer->phone_normalized = static::normalizePhone($customer->phone);
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function outstandingBalance(): float
    {
        $debt = (float) $this->ledgerEntries()->where('entry_type', 'debt')->sum('amount');
        $payments = (float) $this->ledgerEntries()->where('entry_type', 'payment')->sum('amount');

        return round($debt - $payments, 2);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '254' . $digits;
        }

        return $digits;
    }
}
