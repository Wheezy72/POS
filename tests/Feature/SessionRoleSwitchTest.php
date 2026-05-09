<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionRoleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint_reports_unauthenticated_for_guests(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertOk();
        $response->assertJson([
            'authenticated' => false,
            'user' => null,
        ]);
        $response->assertJsonStructure(['csrf_token']);
    }

    public function test_me_endpoint_reports_active_cashier_after_pin_login(): void
    {
        User::query()->create([
            'name' => 'Counter',
            'email' => 'counter@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $this->postJson('/api/login-pin', ['pin' => '0000'])->assertOk();
        $response = $this->getJson('/api/auth/me');

        $response->assertOk();
        $response->assertJson([
            'authenticated' => true,
            'user' => ['role' => 'cashier'],
        ]);
    }

    public function test_admin_pin_login_replaces_cashier_session(): void
    {
        User::query()->create([
            'name' => 'Counter',
            'email' => 'counter@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret-password',
            'pin' => '1234',
            'role' => 'admin',
        ]);

        $this->postJson('/api/login-pin', ['pin' => '0000'])->assertOk();
        $this->getJson('/api/auth/me')->assertJson(['user' => ['role' => 'cashier']]);

        $this->postJson('/api/auth/pin-login', ['pin' => '1234'])->assertOk();
        $this->getJson('/api/auth/me')->assertJson(['user' => ['role' => 'admin']]);
    }

    public function test_logout_returns_csrf_token_and_clears_session(): void
    {
        User::query()->create([
            'name' => 'Counter',
            'email' => 'counter@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $this->postJson('/api/login-pin', ['pin' => '0000'])->assertOk();

        $response = $this->postJson('/api/logout');
        $response->assertOk();
        $response->assertJsonStructure(['message', 'csrf_token']);

        $this->getJson('/api/auth/me')->assertJson(['authenticated' => false]);
    }
}
