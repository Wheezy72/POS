<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $auditLog): void {
            $previousLog = static::query()
                ->latest('created_at')
                ->latest('id')
                ->first();

            if ($previousLog === null) {
                return;
            }

            // Each log stores a fingerprint of the record that came immediately before it.
            // If someone manually edits or removes historical rows in SQLite, the chain breaks
            // and forensic review can detect the tampering.
            $auditLog->previous_hash = hash(
                'sha256',
                $previousLog->id . '|' . $previousLog->created_at?->toISOString()
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
