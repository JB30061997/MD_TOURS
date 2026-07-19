<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileRoadSheetIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_lists_only_the_authenticated_drivers_current_road_sheets(): void
    {
        Carbon::setTestNow('2026-07-18 10:00:00');
        [$user, $driver] = $this->driverUser('mobile-driver@example.test');
        [, $otherDriver] = $this->driverUser('other-mobile-driver@example.test');

        $ownToday = $this->planning($driver, 'OWN-TODAY', '2026-07-18');
        $ownRange = $this->planning($driver, 'OWN-RANGE', '2026-07-16', '2026-07-20');
        $this->planning($otherDriver, 'OTHER-TODAY', '2026-07-18');
        $this->planning($otherDriver, 'OTHER-RANGE', '2026-07-16', '2026-07-20');

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/mobile/driver/road-sheets/plannings')->assertOk();
        $references = collect($response->json('plannings'))->pluck('ref_dossier')->all();

        $this->assertEqualsCanonicalizing(['OWN-TODAY', 'OWN-RANGE'], $references);
        $this->assertNotContains('OTHER-TODAY', $references);
        $this->assertNotContains('OTHER-RANGE', $references);
        $this->assertEqualsCanonicalizing([$ownToday->id, $ownRange->id], collect($response->json('plannings'))->pluck('id')->all());

        Carbon::setTestNow();
    }

    public function test_mobile_saved_list_never_returns_another_drivers_recorded_sheet(): void
    {
        [$user, $driver] = $this->driverUser('saved-mobile-driver@example.test');
        [, $otherDriver] = $this->driverUser('saved-other-driver@example.test');
        $ownSaved = $this->planning($driver, 'OWN-SAVED', '2026-07-01');
        $otherSaved = $this->planning($otherDriver, 'OTHER-SAVED', '2026-07-01');
        RoadSheet::create(['planning_id' => $ownSaved->id, 'status' => 'renseignee']);
        RoadSheet::create(['planning_id' => $otherSaved->id, 'status' => 'renseignee']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/mobile/driver/road-sheets/plannings?status=saved')->assertOk();

        $this->assertSame(['OWN-SAVED'], collect($response->json('plannings'))->pluck('ref_dossier')->all());
    }

    public function test_mobile_driver_cannot_read_or_write_another_drivers_road_sheet(): void
    {
        [$user] = $this->driverUser('protected-mobile-driver@example.test');
        [, $otherDriver] = $this->driverUser('protected-other-driver@example.test');
        $otherPlanning = $this->planning($otherDriver, 'OTHER-PROTECTED', '2026-07-18');

        Sanctum::actingAs($user);
        $this->getJson("/api/mobile/driver/road-sheets/{$otherPlanning->id}")->assertForbidden();
        $this->postJson("/api/mobile/driver/road-sheets/{$otherPlanning->id}", [
            'notes' => 'Unauthorized write',
            'lines' => [],
        ])->assertForbidden();
        $this->assertDatabaseMissing('road_sheets', ['planning_id' => $otherPlanning->id]);
    }

    private function driverUser(string $email): array
    {
        Role::findOrCreate('driver', 'web');
        $user = User::factory()->create(['email' => $email, 'active' => true]);
        $user->assignRole('driver');
        $driver = Driver::create(['user_id' => $user->id, 'name' => $user->name, 'status' => 'Active']);

        return [$user, $driver];
    }

    private function planning(Driver $driver, string $reference, string $start, ?string $end = null): Planning
    {
        return Planning::create([
            'driver_id' => $driver->id,
            'ref_dossier' => $reference,
            'date_du' => $start,
            'date_au' => $end,
        ]);
    }
}
