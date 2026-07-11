<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class AuthSecurityTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    public function test_login_returns_generic_error_for_deactivated_account(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, [
            'role' => 'owner',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_returns_generic_error_for_expired_subscription(): void
    {
        $company = $this->createCompany(['subscription_expires_at' => now()->subDay()]);
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, ['role' => 'owner']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_returns_generic_error_for_wrong_password(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $this->createUser($company, $outlet, ['role' => 'owner']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, ['role' => 'owner']);

        $user->createToken('token-1');
        $user->createToken('token-2');

        $this->actingAs($user);

        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens);
    }
}
