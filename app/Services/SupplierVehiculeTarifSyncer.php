<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\SupplierVehiculeServiceTarif;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierVehiculeTarifSyncer
{
    public function syncFromPlannings(bool $overwriteExisting = true): array
    {
        $dateFrom = Carbon::now()->startOfYear()->toDateString();
        $dateTo = Carbon::now()->toDateString();

        $rows = Planning::query()
            ->with(['service:id,type_service', 'vehicule:id,nombre_places'])
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

        foreach ($rows as $planning) {
            $vehicleSeats = (int) ($planning->vehicule?->nombre_places ?? 0);

            if ($vehicleSeats <= 0) {
                $ignoredMissingSeats++;
                continue;
            }

            $validRows++;
            $processedSupplierIds[$planning->supplier_vehicule_id] = true;
            $typeServiceId = $planning->service?->type_service ?: 'none';
            $key = "{$planning->supplier_vehicule_id}:{$planning->service_id}:{$typeServiceId}:{$vehicleSeats}";

            if (!isset($latestByPair[$key])) {
                $latestByPair[$key] = $planning;
            }
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skippedExisting = 0;
        $duplicatesIgnored = max($validRows - count($latestByPair), 0);

        DB::transaction(function () use ($latestByPair, $overwriteExisting, &$created, &$updated, &$unchanged, &$skippedExisting) {
            foreach ($latestByPair as $planning) {
                $tarif = SupplierVehiculeServiceTarif::firstOrNew([
                    'supplier_vehicule_id' => $planning->supplier_vehicule_id,
                    'service_id' => $planning->service_id,
                    'type_service_id' => $planning->service?->type_service ?: null,
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
        ];
    }
}
