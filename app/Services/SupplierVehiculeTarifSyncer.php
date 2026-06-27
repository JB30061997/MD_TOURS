<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\SupplierVehiculeServiceTarif;
use Illuminate\Support\Facades\DB;

class SupplierVehiculeTarifSyncer
{
    public function syncFromPlannings(bool $overwriteExisting = false): array
    {
        $rows = Planning::query()
            ->whereNotNull('supplier_vehicule_id')
            ->whereNotNull('service_id')
            ->whereNotNull('supplier_price')
            ->where('supplier_price', '>', 0)
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->get(['id', 'supplier_vehicule_id', 'service_id', 'supplier_price']);

        $latestByPair = [];

        foreach ($rows as $planning) {
            $key = "{$planning->supplier_vehicule_id}:{$planning->service_id}";

            if (!isset($latestByPair[$key])) {
                $latestByPair[$key] = $planning;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($latestByPair, $overwriteExisting, &$created, &$updated, &$skipped) {
            foreach ($latestByPair as $planning) {
                $tarif = SupplierVehiculeServiceTarif::firstOrNew([
                    'supplier_vehicule_id' => $planning->supplier_vehicule_id,
                    'service_id' => $planning->service_id,
                ]);

                $newPrice = round((float) $planning->supplier_price, 2);
                $currentPrice = $tarif->exists ? round((float) $tarif->price, 2) : 0.0;
                $shouldFill = !$tarif->exists || $currentPrice <= 0 || $overwriteExisting;

                if (!$shouldFill) {
                    $skipped++;
                    continue;
                }

                $wasExisting = $tarif->exists;
                $tarif->price = $newPrice;
                $tarif->save();

                $wasExisting ? $updated++ : $created++;
            }
        });

        return [
            'pairs_found' => count($latestByPair),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }
}
