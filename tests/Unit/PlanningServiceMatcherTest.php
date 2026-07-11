<?php

namespace Tests\Unit;

use App\Models\Destination;
use App\Models\Planning;
use App\Models\Service;
use App\Services\PlanningServiceMatcher;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PlanningServiceMatcherTest extends TestCase
{
    public function test_it_recommends_departure_transfer_for_a_trip_to_the_airport(): void
    {
        $match = $this->matcher()->recommend(
            $this->planning('Hotel Atlas', 'Marrakech Airport'),
            $this->services()
        );

        $this->assertSame(1, $match['service_id']);
        $this->assertSame('high', $match['confidence']);
    }

    public function test_it_recommends_arrival_transfer_for_a_trip_from_the_airport(): void
    {
        $match = $this->matcher()->recommend(
            $this->planning('Aéroport Marrakech', 'Hotel Atlas'),
            $this->services()
        );

        $this->assertSame(2, $match['service_id']);
        $this->assertSame('high', $match['confidence']);
    }

    public function test_it_keeps_an_unexplained_route_ambiguous(): void
    {
        $match = $this->matcher()->recommend(
            $this->planning('Point A', 'Point B'),
            $this->services()
        );

        $this->assertSame('low', $match['confidence']);
    }

    private function matcher(): PlanningServiceMatcher
    {
        return new PlanningServiceMatcher();
    }

    private function planning(string $departure, string $destination): Planning
    {
        $planning = new Planning(['point_depart' => $departure]);
        $planning->setRelation('destination', new Destination(['name' => $destination]));

        return $planning;
    }

    private function services(): Collection
    {
        return collect([
            new Service(['designation' => 'Departure transfer']),
            new Service(['designation' => 'Arrival transfer']),
            new Service(['designation' => 'Excursion Ourika']),
        ])->each(fn (Service $service, int $index) => $service->id = $index + 1);
    }
}
