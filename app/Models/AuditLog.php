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
                $auditLog->previous_hash = null;
                $auditLog->integrity_hash = hash(
                    'sha256',
                    implode('|', [
                        $auditLog->user_id,
                        $auditLog->action,
                        $auditLog->description,
                        $auditLog->reference_id,
                        'root',
                    ])
                );

                return;
            }

            $auditLog->previous_hash = $previousLog->integrity_hash ?: hash(
                'sha256',
                $previousLog->id . '|' . $previousLog->created_at?->toISOString()
            );
            $auditLog->integrity_hash = hash(
                'sha256',
                implode('|', [
                    $auditLog->user_id,
                    $auditLog->action,
                    $auditLog->description,
                    $auditLog->reference_id,
                    $auditLog->previous_hash,
                ])
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
