<?php

namespace App\Console\Commands;

use App\Models\Planning;
use App\Models\SupplierVehiculeServiceTarif;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerifySupplierVehiculeTarifsFromPlannings extends Command
{
    protected $signature = 'supplier-vehicule-tarifs:verify-planning-sync {--limit=10 : Number of failing examples to print}';

    protected $description = 'Verify current-year planning supplier prices have matching typed vehicle supplier tariffs.';

    public function handle(): int
    {
        $dateFrom = Carbon::now()->startOfYear()->toDateString();
        $dateTo = Carbon::now()->toDateString();
        $exampleLimit = (int) $this->option('limit');

        $rows = Planning::query()
            ->with(['service:id,designation,type_service', 'vehicule:id,matricule,nombre_places', 'supplierVehicule:id,name'])
            ->whereDate('date_du', '>=', $dateFrom)
            ->whereDate('date_du', '<=', $dateTo)
            ->whereNotNull('supplier_vehicule_id')
            ->whereNotNull('service_id')
            ->whereNotNull('vehicule_id')
            ->whereNotNull('supplier_price')
            ->where('supplier_price', '>', 0)
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->get(['id', 'date_du', 'ref_dossier', 'supplier_vehicule_id', 'service_id', 'vehicule_id', 'supplier_price']);

        $latestByKey = [];
        $missingTypeServices = [];
        $ignoredMissingSeats = 0;

        foreach ($rows as $planning) {
            $vehicleSeats = (int) ($planning->vehicule?->nombre_places ?? 0);

            if ($vehicleSeats <= 0) {
                $ignoredMissingSeats++;
                continue;
            }

            $typeServiceId = $planning->service?->type_service;

            if (!$typeServiceId) {
                $missingTypeServices[] = $planning;
                continue;
            }

            $key = "{$planning->supplier_vehicule_id}:{$planning->service_id}:{$typeServiceId}:{$vehicleSeats}";

            if (!isset($latestByKey[$key])) {
                $latestByKey[$key] = $planning;
            }
        }

        $missingTarifs = [];
        $mismatchedPrices = [];

        foreach ($latestByKey as $planning) {
            $vehicleSeats = (int) $planning->vehicule->nombre_places;
            $typeServiceId = (int) $planning->service->type_service;

            $tarif = SupplierVehiculeServiceTarif::query()
                ->where('supplier_vehicule_id', $planning->supplier_vehicule_id)
                ->where('service_id', $planning->service_id)
                ->where('type_service_id', $typeServiceId)
                ->where('vehicle_seats', $vehicleSeats)
                ->first();

            if (!$tarif) {
                $missingTarifs[] = $planning;
                continue;
            }

            $planningPrice = round((float) $planning->supplier_price, 2);
            $tarifPrice = round((float) $tarif->price, 2);

            if (abs($planningPrice - $tarifPrice) > 0.01) {
                $mismatchedPrices[] = [$planning, $tarifPrice];
            }
        }

        $this->info("Period: {$dateFrom} -> {$dateTo}");
        $this->info("Plannings analyzed: {$rows->count()}");
        $this->info('Unique valid tariff keys checked: ' . count($latestByKey));
        $this->info("Ignored missing vehicle seats: {$ignoredMissingSeats}");
        $this->info('Services still without type: ' . count($missingTypeServices));
        $this->info('Missing tariffs: ' . count($missingTarifs));
        $this->info('Mismatched prices: ' . count($mismatchedPrices));

        foreach (array_slice($missingTypeServices, 0, $exampleLimit) as $planning) {
            $this->warn($this->exampleLine('Missing service type', $planning));
        }

        foreach (array_slice($missingTarifs, 0, $exampleLimit) as $planning) {
            $this->warn($this->exampleLine('Missing tariff', $planning));
        }

        foreach (array_slice($mismatchedPrices, 0, $exampleLimit) as [$planning, $tarifPrice]) {
            $this->warn($this->exampleLine("Price mismatch tariff={$tarifPrice}", $planning));
        }

        return count($missingTypeServices) || count($missingTarifs) || count($mismatchedPrices)
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function exampleLine(string $label, Planning $planning): string
    {
        return sprintf(
            '%s: planning #%s ref=%s supplier=%s service=%s vehicle=%s seats=%s price=%s',
            $label,
            $planning->id,
            $planning->ref_dossier ?: '-',
            $planning->supplierVehicule?->name ?: $planning->supplier_vehicule_id,
            $planning->service?->designation ?: $planning->service_id,
            $planning->vehicule?->matricule ?: $planning->vehicule_id,
            $planning->vehicule?->nombre_places ?: '-',
            $planning->supplier_price
        );
    }
}
