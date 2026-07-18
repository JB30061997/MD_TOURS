<?php

namespace App\Support;

use App\Models\Planning;

class MobilePlanningSerializer
{
    public static function enrich(Planning $planning): Planning
    {
        $planning->loadMissing('planningClients.client');

        $clients = $planning->planningClients
            ->map(fn ($relation) => $relation->client)
            ->filter()
            ->unique('id')
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->full_name,
                'full_name' => $client->full_name,
            ])
            ->values();

        $planning->setAttribute('clients', $clients->all());
        $planning->setAttribute('client', $clients->first());

        return $planning;
    }
}
