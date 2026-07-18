<?php

namespace App\Support;

use App\Models\Planning;
use Illuminate\Database\Eloquent\Model;

class MobilePlanningSerializer
{
    public static function person(?Model $person, string $type): ?Model
    {
        if (! $person) {
            return null;
        }

        if ($person->exists) {
            $person->loadMissing('user');
        }

        self::normalizePerson($person, $type);

        return $person;
    }

    public static function relations(): array
    {
        return [
            'service.typeService',
            'driver.user',
            'guide.user',
            'destination',
            'supplierClient.user',
            'planningClients.client.supplierClient.user',
            'supplierVehicule.user',
            'vehicule',
        ];
    }

    public static function enrich(Planning $planning): Planning
    {
        if ($planning->exists) {
            $planning->loadMissing(self::relations());
        } else {
            foreach (['service', 'driver', 'guide', 'destination', 'supplierClient', 'supplierVehicule', 'vehicule'] as $relation) {
                if (! $planning->relationLoaded($relation)) {
                    $planning->setRelation($relation, null);
                }
            }

            if (! $planning->relationLoaded('planningClients')) {
                $planning->setRelation('planningClients', collect());
            }
        }

        $clients = $planning->planningClients
            ->map(fn ($relation) => $relation->client)
            ->filter()
            ->unique('id')
            ->map(function ($client) {
                $name = self::name($client, self::related($client, 'supplierClient'));

                return [
                    'id' => $client->id,
                    'name' => $name,
                    'full_name' => $name,
                    'phone' => $client->phone,
                    'email' => $client->email,
                ];
            })
            ->filter(fn (array $client) => $client['name'] !== null)
            ->values();

        self::person($planning->driver, 'driver');
        self::person($planning->guide, 'guide');

        $serviceType = $planning->service
            ? self::related($planning->service, 'typeService')
            : null;
        $vehicle = $planning->vehicule
            ? [
                'id' => $planning->vehicule->id,
                'name' => trim(implode(' ', array_filter([$planning->vehicule->marque, $planning->vehicule->modele]))) ?: $planning->vehicule->matricule,
                'registration' => $planning->vehicule->matricule,
                'matricule' => $planning->vehicule->matricule,
                'source' => 'vehicle',
            ]
            : ($planning->supplierVehicule ? [
                'id' => $planning->supplierVehicule->id,
                'name' => self::name($planning->supplierVehicule, self::related($planning->supplierVehicule, 'user')),
                'registration' => null,
                'matricule' => null,
                'source' => 'supplier_vehicle',
            ] : null);

        $planning->setAttribute('clients', $clients->all());
        $planning->setAttribute('client', $clients->first());
        $planning->setAttribute('service_type', $serviceType ? [
            'id' => $serviceType->id,
            'name' => $serviceType->designation,
            'designation' => $serviceType->designation,
        ] : null);
        $planning->setAttribute('start_point', $planning->point_depart ? [
            'id' => null,
            'name' => $planning->point_depart,
        ] : null);
        $planning->setAttribute('vehicle', $vehicle);
        $planning->setAttribute('start_date', $planning->date_du?->format('Y-m-d'));
        $planning->setAttribute('end_date', $planning->date_au?->format('Y-m-d'));
        $planning->setAttribute('time', $planning->heure?->format('H:i'));
        $planning->setAttribute('passengers_count', $planning->nbr_personnes);

        return $planning;
    }

    private static function normalizePerson(?Model $person, string $type): void
    {
        if (! $person) {
            return;
        }

        $name = self::name($person, self::related($person, 'user'));
        $person->setAttribute('name', $name);
        $person->setAttribute('full_name', $name);
        $person->setAttribute('profile_type', $type);
    }

    private static function name(?Model $primary, ?Model $fallback = null): ?string
    {
        foreach ([$primary, $fallback] as $model) {
            if (! $model) {
                continue;
            }

            $name = trim((string) ($model->getAttribute('full_name') ?: $model->getAttribute('name')));

            if ($name === '') {
                $name = trim(implode(' ', array_filter([
                    $model->getAttribute('first_name'),
                    $model->getAttribute('last_name'),
                ])));
            }

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private static function related(Model $model, string $relation): ?Model
    {
        return $model->relationLoaded($relation)
            ? $model->getRelation($relation)
            : null;
    }
}
