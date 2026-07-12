<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\TypeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceDescriptionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_update_and_reload_a_service_description(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'services.edit', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);
        $type = TypeService::create(['designation' => 'Circuit']);
        $service = Service::create([
            'designation' => 'Circuit Atlas',
            'type_service' => $type->id,
            'description' => 'Ancienne description',
        ]);

        $this->actingAs($user)
            ->put(route('services.update', $service), [
                'designation' => 'Circuit Atlas',
                'type_service' => $type->id,
                'description' => 'Nouvelle description enregistrée',
            ])
            ->assertRedirect(route('services.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'description' => 'Nouvelle description enregistrée',
        ]);

        $this->actingAs($user)
            ->get(route('services.edit', $service))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Edit')
                ->where('service.description', 'Nouvelle description enregistrée'));
    }
}
