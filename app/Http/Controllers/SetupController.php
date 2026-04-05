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
    public function index(): Response|RedirectResponse
    {
        if (SystemSetting::boolean('is_app_configured', false)) {
            return redirect('/admin');
        }

        return Inertia::render('SetupWizard', [
            'profiles' => [
                [
                    'id' => 'hardware',
                    'name' => 'Hardware',
                    'description' => 'Supports fractional stock, wholesale pricing, and trade accounts.',
                    'toggles' => [
                        'enable_fractional_stock' => true,
                        'enable_credit_sales' => true,
                        'enable_wholesale' => true,
                        'enable_mututho_lock' => false,
                    ],
                ],
                [
                    'id' => 'liquor',
                    'name' => 'Liquor',
                    'description' => 'Supports tots and wholesale sales while enforcing Mututho controls.',
                    'toggles' => [
                        'enable_fractional_stock' => true,
                        'enable_credit_sales' => false,
                        'enable_wholesale' => true,
                        'enable_mututho_lock' => true,
                    ],
                ],
                [
                    'id' => 'minimart',
                    'name' => 'Minimart',
                    'description' => 'Uses standard packaged stock with no credit and no Mututho lock.',
                    'toggles' => [
                        'enable_fractional_stock' => false,
                        'enable_credit_sales' => false,
                        'enable_wholesale' => false,
                        'enable_mututho_lock' => false,
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
                'enable_mututho_lock' => false,
            ],
            'liquor' => [
                'enable_fractional_stock' => true,
                'enable_credit_sales' => false,
                'enable_wholesale' => true,
                'enable_mututho_lock' => true,
            ],
            'minimart' => [
                'enable_fractional_stock' => false,
                'enable_credit_sales' => false,
                'enable_wholesale' => false,
                'enable_mututho_lock' => false,
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

        return redirect('/admin');
    }
}
