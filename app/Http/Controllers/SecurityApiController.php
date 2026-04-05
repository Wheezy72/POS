<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            $shift = DB::transaction(function () use ($user, $validated): Shift {
                $existingOpenShift = Shift::query()
                    ->where('cashier_id', $user->id)
                    ->whereNull('end_time')
                    ->lockForUpdate()
                    ->first();

                if ($existingOpenShift !== null) {
                    throw new RuntimeException('An open shift already exists for this user.');
                }

                $shift = Shift::query()->create([
                    'cashier_id' => $user->id,
                    'start_time' => now(),
                    'end_time' => null,
                    'expected_cash' => round((float) ($validated['opening_cash'] ?? 0), 2),
                    'counted_cash' => null,
                    'variance' => null,
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'open_shift',
                    'description' => "Shift opened with opening cash {$shift->expected_cash}.",
                    'reference_id' => $shift->id,
                ]);

                return $shift;
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
            'shift' => $shift,
        ], 201);
    }

    public function closeShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        try {
            $shift = DB::transaction(function () use ($user, $validated): Shift {
                /** @var Shift|null $shift */
                $shift = Shift::query()
                    ->where('cashier_id', $user->id)
                    ->whereNull('end_time')
                    ->lockForUpdate()
                    ->first();

                if ($shift === null) {
                    throw new RuntimeException('No open shift found for this user.');
                }

                $endTime = now();

                $cashTakings = round((float) Payment::query()
                    ->where('method', 'cash')
                    ->where('status', 'completed')
                    ->whereHas('sale', function ($builder) use ($user, $shift, $endTime) {
                        $builder
                            ->where('user_id', $user->id)
                            ->where('status', 'completed')
                            ->whereBetween('created_at', [$shift->start_time, $endTime]);
                    })
                    ->sum('amount'), 2);

                $expectedCash = round(((float) $shift->expected_cash) + $cashTakings, 2);
                $countedCash = round((float) $validated['counted_cash'], 2);
                $variance = round($countedCash - $expectedCash, 2);

                $shift->update([
                    'end_time' => $endTime,
                    'expected_cash' => $expectedCash,
                    'counted_cash' => $countedCash,
                    'variance' => $variance,
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'close_shift',
                    'description' => "Shift closed. Expected cash: {$expectedCash}. Counted cash: {$countedCash}. Variance: {$variance}.",
                    'reference_id' => $shift->id,
                ]);

                return $shift->fresh();
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
            'shift' => [
                'id' => $shift->id,
                'cashier_id' => $shift->cashier_id,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
            ],
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

        $throttleKey = $this->pinThrottleKey($request, $roles);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'message' => 'Too many PIN login attempts. Terminal locked for 60 seconds.',
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        // PINs are stored as one-way hashes, so they cannot be queried directly.
        // In a POS environment the active user list is typically small enough that
        // verifying against the staff roster is acceptable and keeps the PIN secret at rest.
        $user = $this->findUserByPin($validated['pin']);

        if ($user === null) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => $forbiddenMessage ?? 'This account cannot access this workflow.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

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

    private function pinThrottleKey(Request $request, array $roles): string
    {
        $terminalId = $request->session()->get('terminal_lock_id');

        if ($terminalId === null) {
            $terminalId = (string) Str::uuid();
            $request->session()->put('terminal_lock_id', $terminalId);
        }

        $scope = $roles === [] ? 'any' : implode('-', $roles);

        return implode(':', [
            'pin-login',
            $scope,
            (string) $request->ip(),
            $terminalId,
        ]);
    }
}
