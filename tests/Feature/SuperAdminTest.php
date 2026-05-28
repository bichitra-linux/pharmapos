<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class SuperAdminTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private function createAuthenticatedSuperAdmin(): array
    {
        $admin = $this->createSuperAdmin([
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $admin->createToken('super-admin-token')->plainTextToken;

        return compact('admin', 'token');
    }

    public function test_super_admin_can_login(): void
    {
        $this->createSuperAdmin([
            'email' => 'admin@login.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/super-admin/auth/login', [
            'email' => 'admin@login.com',
            'password' => 'password123',
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

    public function test_super_admin_cannot_login_with_wrong_password(): void
    {
        $this->createSuperAdmin([
            'email' => 'admin@wrong.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/super-admin/auth/login', [
            'email' => 'admin@wrong.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_regular_user_cannot_access_super_admin_routes(): void
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet);

        $response = $this->actingAs($user)
            ->getJson('/api/super-admin/tenants');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized.',
            ]);
    }

    public function test_super_admin_can_list_tenants(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();

        $this->createCompany(['name' => 'Tenant A']);
        $this->createCompany(['name' => 'Tenant B']);

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->getJson('/api/super-admin/tenants');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $tenants = collect($response->json('data'));
        $this->assertTrue($tenants->contains('name', 'Tenant A'));
        $this->assertTrue($tenants->contains('name', 'Tenant B'));
    }

    public function test_super_admin_can_create_tenant(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->postJson('/api/super-admin/tenants', [
                'name' => 'New Tenant Pharmacy',
                'email' => 'new@tenant.com',
                'phone' => '9841666666',
                'address' => 'Bhaktapur, Nepal',
                'admin_name' => 'Tenant Admin',
                'admin_email' => 'admin@newtenant.com',
                'admin_password' => 'password123',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tenant created successfully.',
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'New Tenant Pharmacy',
            'email' => 'new@tenant.com',
            'is_active' => true,
        ]);

        $company = Company::withoutGlobalScopes()
            ->where('name', 'New Tenant Pharmacy')
            ->first();

        $this->assertNotNull($company);
        $this->assertDatabaseHas('outlets', [
            'company_id' => $company->id,
            'is_main_outlet' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'name' => 'Tenant Admin',
            'email' => 'admin@newtenant.com',
            'role' => 'owner',
        ]);
    }

    public function test_super_admin_can_suspend_tenant(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();
        $company = $this->createCompany(['name' => 'To Be Suspended']);

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->patchJson("/api/super-admin/tenants/{$company->id}/suspend", [
                'reason' => 'Payment overdue',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Tenant suspended successfully.',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'suspension_reason' => 'Payment overdue',
        ]);

        $company->refresh();
        $this->assertNotNull($company->suspended_at);
    }

    public function test_suspended_tenant_cannot_login(): void
    {
        $company = $this->createCompany([
            'name' => 'Suspended Co',
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);
        $outlet = $this->createOutlet($company);
        $this->createUser($company, $outlet, [
            'email' => 'suspended@user.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'suspended@user.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_super_admin_can_activate_tenant(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();
        $company = $this->createCompany([
            'name' => 'Suspended Co',
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->patchJson("/api/super-admin/tenants/{$company->id}/activate");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Tenant activated successfully.',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    public function test_super_admin_can_get_profile(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->getJson('/api/super-admin/auth/me');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.email', 'superadmin@test.com');
    }

    public function test_super_admin_can_logout(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->postJson('/api/super-admin/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);
    }

    public function test_super_admin_can_show_tenant(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();
        $company = $this->createCompany(['name' => 'Show Tenant']);

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->getJson("/api/super-admin/tenants/{$company->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.name', 'Show Tenant');
    }

    public function test_super_admin_can_update_tenant(): void
    {
        $super = $this->createAuthenticatedSuperAdmin();
        $company = $this->createCompany(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', "Bearer {$super['token']}")
            ->putJson("/api/super-admin/tenants/{$company->id}", [
                'name' => 'New Name',
                'phone' => '9841777777',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Tenant updated successfully.',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'New Name',
            'phone' => '9841777777',
        ]);
    }

    public function test_unauthenticated_super_admin_route_returns_401(): void
    {
        $response = $this->getJson('/api/super-admin/tenants');

        $response->assertStatus(401);
    }
}
