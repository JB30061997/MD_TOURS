<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\User;
use App\Support\MobilePlanningSerializer;
use PHPUnit\Framework\TestCase;

class MobilePlanningSerializerTest extends TestCase
{
    public function test_it_exposes_all_real_planning_clients_and_a_primary_client(): void
    {
        $first = new Client(['full_name' => 'Mohamed El Amrani']);
        $first->id = 10;
        $second = new Client(['full_name' => 'Sara Bennani']);
        $second->id = 20;

        $planning = new Planning();
        $planning->setRelation('planningClients', collect([
            (new PlanningClient())->setRelation('client', $first),
            (new PlanningClient())->setRelation('client', $second),
        ]));

        $serialized = MobilePlanningSerializer::enrich($planning)->toArray();

        $this->assertSame('Mohamed El Amrani', $serialized['client']['name']);
        $this->assertSame(
            ['Mohamed El Amrani', 'Sara Bennani'],
            array_column($serialized['clients'], 'name')
        );
    }

    public function test_it_exposes_empty_clients_when_none_exist(): void
    {
        $planning = new Planning();
        $planning->setRelation('planningClients', collect());

        $serialized = MobilePlanningSerializer::enrich($planning)->toArray();

        $this->assertNull($serialized['client']);
        $this->assertSame([], $serialized['clients']);
    }

    public function test_it_uses_the_linked_user_name_when_the_driver_profile_name_is_empty(): void
    {
        $user = new User(['name' => 'Igui Ahmed']);
        $user->id = 91;

        $driver = new Driver(['name' => '']);
        $driver->id = 12;
        $driver->setRelation('user', $user);

        $planning = new Planning([
            'point_depart' => 'Marrakech',
            'nbr_personnes' => 4,
        ]);
        $planning->setRelation('driver', $driver);
        $planning->setRelation('planningClients', collect());

        $serialized = MobilePlanningSerializer::enrich($planning)->toArray();

        $this->assertSame('Igui Ahmed', $serialized['driver']['name']);
        $this->assertSame('Igui Ahmed', $serialized['driver']['full_name']);
        $this->assertSame(['id' => null, 'name' => 'Marrakech'], $serialized['start_point']);
        $this->assertSame(4, $serialized['passengers_count']);

        $profile = MobilePlanningSerializer::person($driver, 'driver')->toArray();
        $this->assertSame('Igui Ahmed', $profile['name']);
        $this->assertSame('Igui Ahmed', $profile['full_name']);
    }
}
