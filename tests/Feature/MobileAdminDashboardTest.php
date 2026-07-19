<?php

namespace Tests\Feature;

use App\Models\Planning;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_driver_cannot_access_any_admin_endpoint(): void
    {
        Role::findOrCreate('driver', 'web');
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        Sanctum::actingAs($driver);

        $this->getJson('/api/mobile/admin/dashboard')->assertForbidden();
        $this->getJson('/api/mobile/admin/drivers')->assertForbidden();
        $this->getJson('/api/mobile/admin/plannings')->assertForbidden();
    }

    public function test_admin_dashboard_filters_and_uses_the_shared_dashboard_service(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Planning::create(['ref_dossier' => 'TODAY', 'date_du' => '2026-07-19']);
        Planning::create(['ref_dossier' => 'THIS-WEEK', 'date_du' => '2026-07-18']);
        Planning::create(['ref_dossier' => 'OUTSIDE', 'date_du' => '2026-08-20']);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/mobile/admin/dashboard?filter=week')->assertOk();
        $shared = app(AdminDashboardService::class)->snapshot('week');

        $response->assertJsonPath('stats.plannings_total', 2)
            ->assertJsonPath('stats.plannings_today', 1)
            ->assertJsonPath('access.area', 'admin');
        $this->assertSame($shared['stats'], $response->json('stats'));
    }

    public function test_permission_based_manager_sees_only_authorized_modules(): void
    {
        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('plannings.view', 'web');
        $manager = User::factory()->create();
        $manager->givePermissionTo(['dashboard.view', 'plannings.view']);
        Sanctum::actingAs($manager);

        $this->getJson('/api/mobile/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('access.modules.plannings', true)
            ->assertJsonPath('access.modules.drivers', false);

        $this->getJson('/api/mobile/admin/plannings')->assertOk();
        $this->getJson('/api/mobile/admin/drivers')->assertForbidden();
    }

    public function test_custom_period_is_validated(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/mobile/admin/dashboard?filter=custom&date_from=2026-08-01&date_to=2026-07-01')
            ->assertStatus(422);
    }
}
