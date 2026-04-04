<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_login_overlay_on_the_pos_screen(): void
    {
        $response = $this->get('/pos');

        $response->assertOk();
        $response->assertSeeText('Unlock the register');
        $response->assertSeeText('Staff PIN');
    }

    public function test_cashier_can_log_in_through_the_pin_endpoint(): void
    {
        $user = User::query()->create([
            'name' => 'Front Counter',
            'email' => 'cashier@example.com',
            'password' => 'secret-password',
            'pin' => '0000',
            'role' => 'cashier',
        ]);

        $response = $this->postJson('/api/login-pin', [
            'pin' => '0000',
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'PIN login successful.',
            'session_authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => 'Front Counter',
                'role' => 'cashier',
            ],
        ]);
        $response->assertJsonStructure([
            'csrf_token',
        ]);
        $this->assertAuthenticatedAs($user);
    }
}
