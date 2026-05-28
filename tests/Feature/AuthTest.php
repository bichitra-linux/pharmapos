<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class AuthTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'New Pharmacy',
            'company_email' => 'new@pharmacy.com',
            'company_phone' => '9841111111',
            'company_address' => 'Pokhara, Nepal',
            'name' => 'Sita Owner',
            'email' => 'sita@pharmacy.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'New Pharmacy',
            'email' => 'new@pharmacy.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('outlets', [
            'name' => 'Main Outlet',
            'is_main_outlet' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Sita Owner',
            'email' => 'sita@pharmacy.com',
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, [
            'email' => 'login@test.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@test.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $this->createUser($company, $outlet, [
            'email' => 'wrong@test.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_user_can_get_profile(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, [
            'email' => 'profile@test.com',
            'name' => 'Profile User',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'company',
                    'outlet',
                ],
            ])
            ->assertJsonPath('data.email', 'profile@test.com')
            ->assertJsonPath('data.name', 'Profile User');
    }

    public function test_user_can_logout(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet);

        $token = $user->createToken('pharmapos')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $this->createUser($company, $outlet, [
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ]);
    }

    public function test_user_with_expired_subscription_cannot_login(): void
    {
        $company = $this->createCompany([
            'subscription_expires_at' => now()->subDay(),
        ]);
        $outlet = $this->createOutlet($company);
        $this->createUser($company, $outlet, [
            'email' => 'expired@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'expired@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Subscription has expired. Please renew.',
            ]);
    }

    public function test_register_creates_company_outlet_and_user(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'Integration Pharmacy',
            'company_email' => 'integration@pharmacy.com',
            'company_phone' => '9841222222',
            'company_address' => 'Lalitpur, Nepal',
            'name' => 'Integration User',
            'email' => 'integration@user.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $company = Company::where('name', 'Integration Pharmacy')->first();
        $this->assertNotNull($company);
        $this->assertTrue($company->is_active);

        $outlet = Outlet::where('company_id', $company->id)->first();
        $this->assertNotNull($outlet);
        $this->assertTrue($outlet->is_main_outlet);

        $user = User::withoutGlobalScopes()
            ->where('email', 'integration@user.com')
            ->first();
        $this->assertNotNull($user);
        $this->assertEquals($company->id, $user->company_id);
        $this->assertEquals($outlet->id, $user->outlet_id);
        $this->assertEquals('owner', $user->role->value);
        $this->assertTrue($user->is_active);
    }

    public function test_user_can_update_profile(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, [
            'name' => 'Old Name',
            'phone' => '9841000000',
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'name' => 'New Name',
                'phone' => '9841999999',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone' => '9841999999',
        ]);
    }
}
