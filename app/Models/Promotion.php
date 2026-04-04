<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
