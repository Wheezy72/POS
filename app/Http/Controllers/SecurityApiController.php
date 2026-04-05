<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\ShiftLedger;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class SecurityApiController extends Controller
{
    public function pinLogin(Request $request): JsonResponse
    {
        return $this->authenticatePinLogin($request);
    }

    public function posPinLogin(Request $request): JsonResponse
    {
        return $this->authenticatePinLogin(
            $request,
            ['cashier'],
            'Only cashier accounts can open the POS register.'
        );
    }

    public function managerOverride(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manager_pin' => ['required', 'string', 'regex:/^\d{4}(\d{2})?$/'],
            'action' => ['required', 'string', 'max:255'],
            'reference_id' => ['nullable', 'uuid'],
        ]);

        /** @var User|null $requestingUser */
        $requestingUser = $request->user();

        if ($requestingUser === null) {
            return response()->json([
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        $approver = $this->findUserByPin($validated['manager_pin'], ['manager', 'admin']);

        if ($approver === null) {
            AuditLog::query()->create([
                'user_id' => $requestingUser->id,
                'action' => 'failed_manager_pin',
                'description' => "Manager override failed for action [{$validated['action']}].",
                'reference_id' => $validated['reference_id'] ?? null,
            ]);

            return response()->json([
                'message' => 'Manager approval failed.',
            ], 422);
        }

        AuditLog::query()->create([
            'user_id' => $approver->id,
            'action' => $validated['action'],
            'description' => "Manager override approved by {$approver->name} for cashier {$requestingUser->name}.",
            'reference_id' => $validated['reference_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Manager override approved.',
            'approved_by' => [
                'id' => $approver->id,
                'name' => $approver->name,
                'role' => $approver->role,
            ],
        ]);
    }

    public function openShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            $shiftLedger = DB::transaction(function () use ($user, $validated): ShiftLedger {
                $existingOpenShift = ShiftLedger::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if ($existingOpenShift !== null) {
                    throw new RuntimeException('An open shift already exists for this user.');
                }

                $shiftLedger = ShiftLedger::query()->create([
                    'user_id' => $user->id,
                    'opening_time' => now(),
                    'closing_time' => null,
                    'opening_cash' => round((float) $validated['opening_cash'], 2),
                    'declared_closing_cash' => null,
                    'expected_closing_cash' => null,
                    'status' => 'open',
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'open_shift',
                    'description' => "Shift opened with opening cash {$shiftLedger->opening_cash}.",
                    'reference_id' => $shiftLedger->id,
                ]);

                return $shiftLedger;
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to open shift.',
            ], 500);
        }

        return response()->json([
            'message' => 'Shift opened successfully.',
            'shift' => $shiftLedger,
        ], 201);
    }

    public function closeShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'declared_cash' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            [$shiftLedger, $discrepancy] = DB::transaction(function () use ($user, $validated): array {
                /** @var ShiftLedger|null $shiftLedger */
                $shiftLedger = ShiftLedger::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if ($shiftLedger === null) {
                    throw new RuntimeException('No open shift found for this user.');
                }

                $closingTime = now();

                // Blind Z-read protection:
                // expected cash is computed server-side from recorded cash payments only,
                // so the cashier cannot influence it from the client payload.
                $cashTakings = round((float) Payment::query()
                    ->where('method', 'cash')
                    ->where('status', 'completed')
                    ->whereHas('sale', function ($builder) use ($user, $shiftLedger, $closingTime) {
                        $builder
                            ->where('user_id', $user->id)
                            ->where('status', 'completed')
                            ->whereBetween('created_at', [$shiftLedger->opening_time, $closingTime]);
                    })
                    ->sum('amount'), 2);

                $expectedCash = round(((float) $shiftLedger->opening_cash) + $cashTakings, 2);
                $declaredCash = round((float) $validated['declared_cash'], 2);
                $discrepancy = round($declaredCash - $expectedCash, 2);

                $shiftLedger->update([
                    'closing_time' => $closingTime,
                    'declared_closing_cash' => $declaredCash,
                    'expected_closing_cash' => $expectedCash,
                    'status' => 'closed',
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'close_shift',
                    'description' => "Shift closed. Expected cash: {$expectedCash}. Declared cash: {$declaredCash}. Discrepancy: {$discrepancy}.",
                    'reference_id' => $shiftLedger->id,
                ]);

                return [$shiftLedger->fresh(), $discrepancy];
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to close shift.',
            ], 500);
        }

        return response()->json([
            'message' => 'Shift closed successfully.',
            'shift' => $shiftLedger,
            'discrepancy' => $discrepancy,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function findUserByPin(string $pin, array $roles = []): ?User
    {
        $query = User::query()->whereNotNull('pin');

        if ($roles !== []) {
            $query->whereIn('role', $roles);
        }

        /** @var User $user */
        foreach ($query->cursor() as $user) {
            if (Hash::check($pin, $user->pin)) {
                return $user;
            }
        }

        return null;
    }

    private function authenticatePinLogin(
        Request $request,
        array $roles = [],
        ?string $forbiddenMessage = null,
    ): JsonResponse {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{4}(\d{2})?$/'],
        ]);

        // PINs are stored as one-way hashes, so they cannot be queried directly.
        // In a POS environment the active user list is typically small enough that
        // verifying against the staff roster is acceptable and keeps the PIN secret at rest.
        $user = $this->findUserByPin($validated['pin']);

        if ($user === null) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => $forbiddenMessage ?? 'This account cannot access this workflow.',
            ], 403);
        }

        // The routes are registered in web.php, so session authentication is a valid
        // offline-friendly option even when the API is consumed by a local device.
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'pin_login',
            'description' => 'User authenticated via PIN login.',
            'reference_id' => null,
        ]);

        return response()->json([
            'message' => 'PIN login successful.',
            'session_authenticated' => true,
            'csrf_token' => $request->session()->token(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
}
