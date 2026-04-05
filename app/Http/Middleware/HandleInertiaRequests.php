<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $isCashier = $user !== null && $user->role === 'cashier';

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $isCashier ? [
                    'id' => (string) $user->getAuthIdentifier(),
                    'name' => $user->name,
                    'role' => $user->role,
                ] : null,
                'blockedRole' => $user !== null && ! $isCashier ? $user->role : null,
            ],
            'csrfToken' => csrf_token(),
        ];
    }
}
