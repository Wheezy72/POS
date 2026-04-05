<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $justConfigured = (bool) $request->session()->get('setup_complete', false);

        if (SystemSetting::boolean('is_app_configured', false) && ! $justConfigured) {
            return redirect('/pos');
        }

        return Inertia::render('SetupWizard', [
            'justConfigured' => $justConfigured,
            'profiles' => [
                [
                    'id' => 'hardware',
                    'name' => 'Hardware store',
                    'description' => 'Best for stores that sell measured items, bulk items, and account sales.',
                    'toggles' => [
                        'enable_fractional_stock' => true,
                        'enable_credit_sales' => true,
                        'enable_wholesale' => true,
                        'enable_sales_hours_lock' => false,
                    ],
                ],
                [
                    'id' => 'liquor',
                    'name' => 'Liquor store',
                    'description' => 'Best for bottle stores that need sales-hour controls and no credit sales.',
                    'toggles' => [
                        'enable_fractional_stock' => true,
                        'enable_credit_sales' => false,
                        'enable_wholesale' => true,
                        'enable_sales_hours_lock' => true,
                    ],
                ],
                [
                    'id' => 'minimart',
                    'name' => 'Mini market',
                    'description' => 'Best for everyday packaged goods with simple pricing and no credit sales.',
                    'toggles' => [
                        'enable_fractional_stock' => false,
                        'enable_credit_sales' => false,
                        'enable_wholesale' => false,
                        'enable_sales_hours_lock' => false,
                    ],
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_type' => ['required', 'in:hardware,liquor,minimart'],
        ]);

        $matrix = [
            'hardware' => [
                'enable_fractional_stock' => true,
                'enable_credit_sales' => true,
                'enable_wholesale' => true,
                'enable_sales_hours_lock' => false,
            ],
            'liquor' => [
                'enable_fractional_stock' => true,
                'enable_credit_sales' => false,
                'enable_wholesale' => true,
                'enable_sales_hours_lock' => true,
            ],
            'minimart' => [
                'enable_fractional_stock' => false,
                'enable_credit_sales' => false,
                'enable_wholesale' => false,
                'enable_sales_hours_lock' => false,
            ],
        ];

        foreach ($matrix[$validated['business_type']] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value ? 'true' : 'false'],
            );
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => 'is_app_configured'],
            ['value' => 'true'],
        );

        return redirect('/setup')->with('setup_complete', true);
    }
}
