<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\Service;
use App\Models\SupplierVehiculeServiceTarif;
use App\Models\TypeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierVehiculeTarifSyncer
{
    public function syncFromPlannings(bool $overwriteExisting = true): array
    {
        $dateFrom = Carbon::now()->startOfYear()->toDateString();
        $dateTo = Carbon::now()->toDateString();

        $rows = Planning::query()
            ->with(['service:id,designation,type_service', 'vehicule:id,nombre_places'])
            ->whereDate('date_du', '>=', $dateFrom)
            ->whereDate('date_du', '<=', $dateTo)
            ->whereNotNull('supplier_vehicule_id')
            ->whereNotNull('service_id')
            ->whereNotNull('vehicule_id')
            ->whereNotNull('supplier_price')
            ->where('supplier_price', '>', 0)
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->get(['id', 'date_du', 'supplier_vehicule_id', 'service_id', 'vehicule_id', 'supplier_price']);

        $latestByPair = [];
        $validRows = 0;
        $ignoredMissingSeats = 0;
        $processedSupplierIds = [];
        $servicesTyped = 0;

        foreach ($rows as $planning) {
            $vehicleSeats = (int) ($planning->vehicule?->nombre_places ?? 0);

            if ($vehicleSeats <= 0) {
                $ignoredMissingSeats++;
                continue;
            }

            $validRows++;
            $processedSupplierIds[$planning->supplier_vehicule_id] = true;
            $typeServiceId = $this->typeServiceIdForService($planning->service, $servicesTyped) ?: 'none';
            $key = "{$planning->supplier_vehicule_id}:{$planning->service_id}:{$typeServiceId}:{$vehicleSeats}";

            $planning->resolved_type_service_id = $typeServiceId === 'none' ? null : $typeServiceId;

            if (!isset($latestByPair[$key])) {
                $latestByPair[$key] = $planning;
            }
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skippedExisting = 0;
        $duplicatesIgnored = max($validRows - count($latestByPair), 0);

        $legacyTyped = 0;

        DB::transaction(function () use ($latestByPair, $overwriteExisting, &$created, &$updated, &$unchanged, &$skippedExisting, &$legacyTyped) {
            foreach ($latestByPair as $planning) {
                $typeServiceId = $planning->resolved_type_service_id;

                $tarif = SupplierVehiculeServiceTarif::firstOrNew([
                    'supplier_vehicule_id' => $planning->supplier_vehicule_id,
                    'service_id' => $planning->service_id,
                    'type_service_id' => $typeServiceId,
                    'vehicle_seats' => (int) $planning->vehicule->nombre_places,
                ]);

                $newPrice = round((float) $planning->supplier_price, 2);
                $currentPrice = $tarif->exists ? round((float) $tarif->price, 2) : 0.0;
                $shouldFill = !$tarif->exists || $currentPrice <= 0 || $overwriteExisting;

                if (!$shouldFill) {
                    $skippedExisting++;
                    continue;
                }

                if ($tarif->exists && abs($currentPrice - $newPrice) < 0.001) {
                    $unchanged++;
                    continue;
                }

                $wasExisting = $tarif->exists;
                $tarif->price = $newPrice;
                $tarif->save();

                $wasExisting ? $updated++ : $created++;
            }

            $legacyTyped = $this->normalizeLegacyNullTypeTarifs();
        });

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'plannings_analyzed' => $rows->count(),
            'valid_plannings' => $validRows,
            'suppliers_processed' => count($processedSupplierIds),
            'pairs_found' => count($latestByPair),
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped_existing' => $skippedExisting,
            'duplicates_ignored' => $duplicatesIgnored,
            'ignored_missing_seats' => $ignoredMissingSeats,
            'services_typed' => $servicesTyped,
            'legacy_tarifs_typed' => $legacyTyped,
        ];
    }

    private function typeServiceIdForService(?Service $service, int &$servicesTyped = 0): ?int
    {
        if (!$service) {
            return null;
        }

        if ($service->type_service) {
            return (int) $service->type_service;
        }

        $type = $this->inferTypeService($service->designation);

        if (!$type) {
            return null;
        }

        $service->forceFill(['type_service' => $type->id])->save();
        $service->type_service = $type->id;
        $servicesTyped++;

        return (int) $type->id;
    }

    private function normalizeLegacyNullTypeTarifs(): int
    {
        $updated = 0;

        SupplierVehiculeServiceTarif::query()
            ->with('service:id,designation,type_service')
            ->whereNull('type_service_id')
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->get()
            ->each(function (SupplierVehiculeServiceTarif $tarif) use (&$updated) {
                $typeServiceId = $this->typeServiceIdForService($tarif->service);

                if (!$typeServiceId) {
                    return;
                }

                $existing = SupplierVehiculeServiceTarif::query()
                    ->where('supplier_vehicule_id', $tarif->supplier_vehicule_id)
                    ->where('service_id', $tarif->service_id)
                    ->where('type_service_id', $typeServiceId)
                    ->where('vehicle_seats', $tarif->vehicle_seats)
                    ->whereKeyNot($tarif->id)
                    ->first();

                if ($existing) {
                    if ((float) $existing->price <= 0 && (float) $tarif->price > 0) {
                        $existing->price = $tarif->price;
                        $existing->save();
                    }

                    $tarif->delete();
                    $updated++;
                    return;
                }

                $tarif->type_service_id = $typeServiceId;
                $tarif->save();
                $updated++;
            });

        return $updated;
    }

    private function inferTypeService(?string $designation): ?TypeService
    {
        $label = mb_strtolower(trim((string) $designation));

        if ($label === '') {
            return null;
        }

        $target = match (true) {
            str_contains($label, 'circuit') => 'Circuit',
            str_contains($label, 'excursion'),
            str_contains($label, 'agafay'),
            str_contains($label, 'ourika'),
            str_contains($label, 'ouzoud'),
            str_contains($label, 'essaouira'),
            str_contains($label, 'visite') => 'Excursion',
            str_contains($label, 'airport'),
            str_contains($label, 'aeroport'),
            str_contains($label, 'aéroport'),
            str_contains($label, 'apt'),
            str_contains($label, 'arrivée'),
            str_contains($label, 'arrivee'),
            str_contains($label, 'arrival'),
            str_contains($label, 'départ'),
            str_contains($label, 'depart'),
            str_contains($label, 'departure') => 'Transfert aéroport - hôtel - aéroport',
            str_contains($label, 'inter-ville'),
            str_contains($label, 'inter ville') => 'Transfert inter-ville',
            str_contains($label, 'transfer'),
            str_contains($label, 'transfert') => 'Transfer',
            default => 'Transfer',
        };

        return TypeService::firstOrCreate(['designation' => $target]);
    }
}
