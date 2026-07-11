<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class ObserverTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet, ['role' => 'owner']);
        $this->actingAs($user);
    }

    public function test_audit_log_created_on_user_update(): void
    {
        $user = User::where('email', 'user@test.com')->first();
        $user->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'model_type' => User::class,
        ]);
    }

    public function test_audit_log_created_on_outlet_create(): void
    {
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'model_type' => Outlet::class,
        ]);
    }
}
