<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverWebPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_login_redirects_to_driver_dashboard(): void
    {
        $user = $this->driverUser()[0];

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('driver.dashboard'));
    }

    public function test_non_driver_login_keeps_the_existing_dashboard_redirect(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_driver_only_sees_and_opens_own_plannings(): void
    {
        [$user, $driver] = $this->driverUser();
        [, $otherDriver] = $this->driverUser('other@example.test');
        $own = Planning::create(['driver_id' => $driver->id, 'ref_dossier' => 'OWN-001', 'date_du' => today()]);
        $other = Planning::create(['driver_id' => $otherDriver->id, 'ref_dossier' => 'OTHER-001', 'date_du' => today()]);

        $this->actingAs($user)->get(route('driver.plannings.index'))->assertOk()->assertSee('OWN-001')->assertDontSee('OTHER-001');
        $this->actingAs($user)->get(route('driver.plannings.show', $own))->assertOk();
        $this->actingAs($user)->get(route('driver.plannings.show', $other))->assertRedirect(route('dashboard'));
    }

    public function test_five_day_circuit_generates_five_lines_and_update_does_not_duplicate_sheet(): void
    {
        [$user, $driver] = $this->driverUser();
        $service = Service::create(['designation' => 'Circuit 5 jours']);
        $planning = Planning::create(['driver_id' => $driver->id, 'service_id' => $service->id, 'ref_dossier' => 'CIR-005', 'date_du' => '2026-08-01', 'date_au' => '2026-08-05']);

        $this->actingAs($user)->get(route('driver.road-sheets.edit', $planning))->assertOk();
        $sheet = RoadSheet::where('planning_id', $planning->id)->firstOrFail();
        $this->assertCount(5, $sheet->lines);

        $lines = $sheet->lines->map(fn ($line, $index) => [
            'date' => $line->date->toDateString(),
            'departure_kms' => 1000 + ($index * 100),
            'arrival_kms' => 1080 + ($index * 100),
            'gasoline' => 10,
            'jawaz' => 5,
            'other_expenses' => 2,
            'notes' => null,
        ])->all();

        $payload = ['pre_service_km' => 240, 'pre_service_origin' => 'Marrakech', 'pre_service_note' => 'Approche', 'notes' => null, 'lines' => $lines];
        $this->actingAs($user)->put(route('driver.road-sheets.update', $planning), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('driver.road-sheets.update', $planning), $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, RoadSheet::where('planning_id', $planning->id)->count());
        $this->assertSame(240, $sheet->fresh()->pre_service_km);
        $this->assertSame(400, (int) $sheet->fresh('lines')->lines->sum('distance'));
    }

    private function driverUser(string $email = 'driver@example.test'): array
    {
        Role::findOrCreate('driver', 'web');
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('driver');
        $driver = Driver::create(['user_id' => $user->id, 'name' => $user->name, 'status' => 'Active']);

        return [$user, $driver];
    }
}
