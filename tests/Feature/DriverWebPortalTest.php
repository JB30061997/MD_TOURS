<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\Service;
use App\Models\User;
use App\Services\RoadSheetOperationalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
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

    public function test_igui_today_matches_the_three_admin_plannings_without_multi_day_or_duplicates(): void
    {
        Carbon::setTestNow('2026-07-18 12:00:00');
        [$user, $igui] = $this->driverUser('igui@example.test', 'Igui Abdellatif');
        [, $otherDriver] = $this->driverUser('other-igui-test@example.test');

        foreach (range(1, 3) as $index) {
            Planning::create(['driver_id' => $igui->id, 'ref_dossier' => "IGUI-TODAY-{$index}", 'date_du' => '2026-07-18']);
        }
        foreach (range(1, 199) as $index) {
            Planning::create(['driver_id' => $igui->id, 'ref_dossier' => "IGUI-PAST-{$index}", 'date_du' => '2026-07-01']);
        }
        Planning::create(['driver_id' => $igui->id, 'ref_dossier' => 'IGUI-MULTI-1', 'date_du' => '2026-07-16', 'date_au' => '2026-07-20']);
        Planning::create(['driver_id' => $igui->id, 'ref_dossier' => 'IGUI-MULTI-2', 'date_du' => '2026-07-17', 'date_au' => '2026-07-19']);
        Planning::create(['driver_id' => $igui->id, 'ref_dossier' => 'IGUI-UPCOMING-1', 'date_du' => '2026-07-19']);
        Planning::create(['driver_id' => $igui->id, 'ref_dossier' => 'IGUI-UPCOMING-2', 'date_du' => '2026-07-20']);
        Planning::create(['driver_id' => $otherDriver->id, 'ref_dossier' => 'OTHER-TODAY', 'date_du' => '2026-07-18']);

        $this->actingAs($user)->get(route('driver.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 206)
                ->where('stats.past', 201)
                ->where('stats.today', 3)
                ->where('stats.upcoming', 2)
                ->has('plannings', 206));

        $this->actingAs($user)->get(route('driver.plannings.index', ['period' => 'today']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('plannings.data', 3)
                ->where('plannings.data', fn ($items) => collect($items)->pluck('id')->unique()->count() === 3));

        Sanctum::actingAs($user);
        $complete = $this->getJson('/api/mobile/dashboard?per_page=500')->assertOk();
        $this->assertSame(206, $complete->json('stats.total_plannings'));
        $this->assertSame(201, $complete->json('stats.past_plannings'));
        $this->assertSame(3, $complete->json('stats.today_plannings'));
        $this->assertSame(2, $complete->json('stats.upcoming_plannings'));
        $this->assertCount(206, $complete->json('latest_plannings'));
        $this->assertCount(206, collect($complete->json('latest_plannings'))->pluck('id')->unique());
        $todayChart = collect($complete->json('chart'))->firstWhere('label', '2026-07-18');
        $this->assertSame(3, $todayChart['count']);

        $response = $this->getJson('/api/mobile/dashboard?status=today&per_page=100')->assertOk();
        $this->assertSame(3, $response->json('stats.total_plannings'));
        $this->assertCount(3, $response->json('latest_plannings'));
        $this->assertCount(3, collect($response->json('latest_plannings'))->pluck('id')->unique());

        Carbon::setTestNow();
    }

    public function test_pre_service_odometers_accept_real_vehicle_values_and_compute_only_the_distance(): void
    {
        [$user, $driver] = $this->driverUser('odometer-driver@example.test');
        $planning = Planning::create([
            'driver_id' => $driver->id,
            'ref_dossier' => 'ODO-001',
            'date_du' => '2026-07-18',
        ]);
        Sanctum::actingAs($user);

        foreach ([0, 5000, 10001, 85000, 185430] as $odometer) {
            $this->postJson("/api/mobile/driver/road-sheets/{$planning->id}", [
                'pre_service_odometer_start' => $odometer,
                'pre_service_odometer_end' => $odometer + 25,
                'lines' => [],
            ])->assertOk()
                ->assertJsonPath('road_sheet.pre_service_odometer_start', $odometer)
                ->assertJsonPath('road_sheet.pre_service_odometer_end', $odometer + 25)
                ->assertJsonPath('totals.pre_service_km', 25)
                ->assertJsonPath('totals.total_distance', 25);
        }

        $this->postJson("/api/mobile/driver/road-sheets/{$planning->id}", [
            'pre_service_odometer_start' => -1,
            'pre_service_odometer_end' => 25,
        ])->assertUnprocessable()->assertJsonValidationErrors('pre_service_odometer_start');

        $this->postJson("/api/mobile/driver/road-sheets/{$planning->id}", [
            'pre_service_odometer_start' => '185430km',
            'pre_service_odometer_end' => 185455,
        ])->assertUnprocessable()->assertJsonValidationErrors('pre_service_odometer_start');
    }

    public function test_road_sheet_operational_summary_tracks_zero_partial_and_completed_circuits(): void
    {
        [, $driver] = $this->driverUser('summary-driver@example.test');
        $service = Service::create(['designation' => 'Circuit 5 jours']);
        $summaries = app(RoadSheetOperationalService::class);

        foreach ([0, 3, 5] as $completedDays) {
            $planning = Planning::create([
                'driver_id' => $driver->id,
                'service_id' => $service->id,
                'ref_dossier' => "SUMMARY-{$completedDays}",
                'date_du' => '2026-08-01',
                'date_au' => '2026-08-05',
            ]);
            $sheet = RoadSheet::create([
                'planning_id' => $planning->id,
                'pre_service_odometer_start' => $completedDays === 3 ? 185430 : null,
                'pre_service_odometer_end' => $completedDays === 3 ? 185550 : null,
                'pre_service_km' => $completedDays === 3 ? 120 : 0,
            ]);

            foreach (range(0, 4) as $index) {
                $sheet->lines()->create([
                    'date' => Carbon::parse('2026-08-01')->addDays($index),
                    'departure_kms' => $index < $completedDays ? 10000 + ($index * 100) : null,
                    'arrival_kms' => $index < $completedDays ? 10080 + ($index * 100) : null,
                    'distance' => $index < $completedDays ? 80 : null,
                    'sort_order' => $index,
                ]);
            }

            $summary = $summaries->summarize($planning->fresh(['service', 'roadSheet.lines']));
            $this->assertSame(5, $summary['total_days']);
            $this->assertSame($completedDays, $summary['completed_days']);
            $this->assertSame(5 - $completedDays, $summary['remaining_days']);
            $this->assertCount(5, $summary['days']);
            $this->assertSame($completedDays * 80, $summary['circuit_distance']);
            $this->assertSame($completedDays === 3 ? 120 : 0, $summary['pre_service_distance']);
            $this->assertSame(($completedDays * 80) + ($completedDays === 3 ? 120 : 0), $summary['real_total_distance']);
        }
    }

    public function test_admin_road_sheet_list_filters_driver_and_status_without_pagination_duplicates(): void
    {
        [$user, $driver] = $this->driverUser('list-driver@example.test');
        [, $otherDriver] = $this->driverUser('list-other@example.test');
        $service = Service::create(['designation' => 'Circuit 5 jours']);

        foreach (range(1, 21) as $index) {
            Planning::create([
                'driver_id' => $driver->id,
                'service_id' => $service->id,
                'ref_dossier' => "LIST-{$index}",
                'date_du' => '2026-08-01',
                'date_au' => '2026-08-05',
            ]);
        }
        Planning::create([
            'driver_id' => $otherDriver->id,
            'service_id' => $service->id,
            'ref_dossier' => 'OTHER-LIST',
            'date_du' => '2026-08-01',
        ]);

        $partial = Planning::where('ref_dossier', 'LIST-1')->firstOrFail();
        $sheet = RoadSheet::create(['planning_id' => $partial->id]);
        foreach (range(0, 4) as $index) {
            $sheet->lines()->create([
                'date' => Carbon::parse('2026-08-01')->addDays($index),
                'departure_kms' => $index < 3 ? 1000 + ($index * 100) : null,
                'arrival_kms' => $index < 3 ? 1080 + ($index * 100) : null,
                'sort_order' => $index,
            ]);
        }

        $this->withoutMiddleware()->actingAs($user)
            ->get(route('road-sheets.index', ['driver_id' => $driver->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('plannings.total', 21)
                ->has('plannings.data', 20)
                ->where('plannings.data', fn ($items) => collect($items)->pluck('id')->unique()->count() === 20));

        $this->withoutMiddleware()->actingAs($user)
            ->get(route('road-sheets.index', ['driver_id' => $driver->id, 'status' => 'partial']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('plannings.total', 1)
                ->has('plannings.data.0.road_sheet_summary.days', 5)
                ->where('plannings.data.0.road_sheet_summary.completed_days', 3)
                ->where('plannings.data.0.road_sheet_summary.remaining_days', 2));
    }

    private function driverUser(string $email = 'driver@example.test', ?string $name = null): array
    {
        Role::findOrCreate('driver', 'web');
        $user = User::factory()->create(['email' => $email, 'name' => $name ?: fake()->name()]);
        $user->assignRole('driver');
        $driver = Driver::create(['user_id' => $user->id, 'name' => $user->name, 'status' => 'Active']);

        return [$user, $driver];
    }
}
