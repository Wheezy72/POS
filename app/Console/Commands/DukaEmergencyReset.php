<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class DukaEmergencyReset extends Command
{
    protected $signature = 'duka:emergency-reset {email : The admin email address to recover}';

    protected $description = 'Generate a temporary offline recovery PIN for an admin account.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        try {
            [$user, $temporaryPin] = DB::transaction(function () use ($email): array {
                /** @var User|null $user */
                $user = User::query()
                    ->where('email', $email)
                    ->where('role', 'admin')
                    ->lockForUpdate()
                    ->first();

                if ($user === null) {
                    throw new RuntimeException('Admin account not found for the provided email.');
                }

                // Recovery credentials must be generated with a cryptographically secure source
                // because this PIN can bypass the normal in-store manager escalation flow.
                $temporaryPin = $this->generateRecoveryPin();

                $user->update([
                    'pin' => Hash::make($temporaryPin),
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'emergency_pin_reset',
                    'description' => 'Emergency recovery PIN generated through the offline admin reset command.',
                    'reference_id' => null,
                ]);

                return [$user, $temporaryPin];
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Emergency reset failed.');

            return self::FAILURE;
        }

        $this->warn('Temporary recovery PIN generated. Store it securely and rotate it after first use.');
        $this->line("Admin: {$user->email}");
        $this->info("Recovery PIN: {$temporaryPin}");

        return self::SUCCESS;
    }

    private function generateRecoveryPin(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;
        $pin = '';

        for ($index = 0; $index < 8; $index++) {
            $pin .= $alphabet[random_int(0, $maxIndex)];
        }

        return $pin;
    }
}
