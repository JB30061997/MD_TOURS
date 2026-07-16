<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Planning;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceDestinationStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_page_filters_statistics_by_service_destination_and_period(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $transfer = Service::create(['designation' => 'Transfert aéroport Casablanca']);
        $excursion = Service::create(['designation' => 'Excursion Essaouira']);
        $casablanca = Destination::create(['name' => 'Aéroport Mohammed V', 'city' => 'Casablanca']);
        $essaouira = Destination::create(['name' => 'Essaouira', 'city' => 'Essaouira']);

        Planning::create(['date_du' => '2026-07-03', 'ref_dossier' => 'CAS-001', 'service_id' => $transfer->id, 'destination_id' => $casablanca->id, 'budget' => 500, 'supplier_price' => 300]);
        Planning::create(['date_du' => '2026-07-08', 'ref_dossier' => 'CAS-002', 'service_id' => $transfer->id, 'destination_id' => $casablanca->id, 'budget' => 600, 'supplier_price' => 350]);
        Planning::create(['date_du' => '2026-07-09', 'ref_dossier' => 'ESS-001', 'service_id' => $excursion->id, 'destination_id' => $essaouira->id, 'budget' => 900, 'supplier_price' => 600]);
        Planning::create(['date_du' => '2026-08-01', 'ref_dossier' => 'CAS-003', 'service_id' => $transfer->id, 'destination_id' => $casablanca->id, 'budget' => 700, 'supplier_price' => 400]);

        $this->actingAs($user)
            ->get(route('services.index', [
                'stats_date_from' => '2026-07-01',
                'stats_date_to' => '2026-07-31',
                'stats_service_id' => $transfer->id,
                'stats_destination_id' => $casablanca->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Index')
                ->where('statsSummary.total_trips', 2)
                ->where('statsSummary.services_count', 1)
                ->where('statsSummary.destinations_count', 1)
                ->where('statsSummary.total_budget', 1100)
                ->has('serviceDestinationStats', 1)
                ->where('serviceDestinationStats.0.service_name', 'Transfert aéroport Casablanca')
                ->where('serviceDestinationStats.0.destination_city', 'Casablanca')
                ->where('serviceDestinationStats.0.total_trips', 2)
                ->where('serviceDestinationStats.0.total_dossiers', 2)
                ->where('serviceDestinationStats.0.gross_margin', 450));
    }
}
