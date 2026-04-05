<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_screen_is_available_when_the_app_is_not_configured(): void
    {
        $response = $this->get('/setup');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SetupWizard')
            ->has('profiles', 3)
            ->where('profiles.0.id', 'hardware')
            ->where('profiles.1.id', 'liquor')
            ->where('profiles.2.id', 'minimart'));
    }

    public function test_admin_route_redirects_to_setup_until_configuration_is_complete(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/setup');
    }

    public function test_setup_profile_applies_liquor_matrix_and_marks_app_configured(): void
    {
        $response = $this->post('/setup', [
            'business_type' => 'liquor',
        ]);

        $response->assertRedirect('/admin');

        $this->assertSame(true, SystemSetting::boolean('is_app_configured', false));
        $this->assertSame(true, SystemSetting::boolean('enable_fractional_stock', false));
        $this->assertSame(false, SystemSetting::boolean('enable_credit_sales', true));
        $this->assertSame(true, SystemSetting::boolean('enable_wholesale', false));
        $this->assertSame(true, SystemSetting::boolean('enable_mututho_lock', false));
    }
}
