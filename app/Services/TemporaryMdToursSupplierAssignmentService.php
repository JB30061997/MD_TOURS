<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\SupplierVehicule;
use Illuminate\Support\Facades\DB;

/**
 * TEMPORAIRE — Affectation corrective des chauffeurs internes MD TOURS.
 *
 * Ce service est volontairement isolé afin de pouvoir supprimer facilement
 * la fonctionnalité une fois le nettoyage des anciens plannings terminé.
 */
class TemporaryMdToursSupplierAssignmentService
{
    private const INTERNAL_DRIVER_SUFFIX = 'DRIVER MD TOURS';

    private const SUPPLIER_NAME = 'MD TOURS';

    public function run(): array
    {
        $missingSupplierQuery = Planning::query()->whereNull('supplier_vehicule_id');
        $analyzed = (clone $missingSupplierQuery)->count();
        $supplier = $this->findMdToursSupplier();

        if (! $supplier) {
            return [
                'success' => false,
                'message' => 'Le fournisseur véhicule « MD TOURS » est introuvable. Aucun planning n’a été modifié.',
                'supplier_vehicule_id' => null,
                'analyzed' => $analyzed,
                'corrected' => 0,
                'ignored' => $analyzed,
                'remaining' => $analyzed,
            ];
        }

        $corrected = DB::transaction(function () use ($supplier): int {
            $updated = 0;

            Planning::query()
                ->whereNull('supplier_vehicule_id')
                ->whereNotNull('driver_id')
                ->with('driver:id,name')
                ->select(['id', 'driver_id'])
                ->orderBy('id')
                ->chunkById(250, function ($plannings) use ($supplier, &$updated) {
                    foreach ($plannings as $planning) {
                        if (! self::isInternalMdToursDriver($planning->driver?->name)) {
                            continue;
                        }

                        $updated += Planning::query()
                            ->whereKey($planning->id)
                            ->whereNull('supplier_vehicule_id')
                            ->update(['supplier_vehicule_id' => $supplier->id]);
                    }
                });

            return $updated;
        });

        $remaining = Planning::query()->whereNull('supplier_vehicule_id')->count();

        return [
            'success' => true,
            'message' => sprintf(
                'Traitement terminé : %d planning(s) affecté(s) au fournisseur véhicule MD TOURS. Il reste %d planning(s) sans fournisseur véhicule.',
                $corrected,
                $remaining
            ),
            'supplier_vehicule_id' => $supplier->id,
            'analyzed' => $analyzed,
            'corrected' => $corrected,
            'ignored' => max($analyzed - $corrected, 0),
            'remaining' => $remaining,
        ];
    }

    public static function isInternalMdToursDriver(?string $name): bool
    {
        if ($name === null) {
            return false;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($name));
        $normalized = mb_strtoupper($normalized ?? '', 'UTF-8');

        return $normalized !== '' && str_ends_with($normalized, self::INTERNAL_DRIVER_SUFFIX);
    }

    private function findMdToursSupplier(): ?SupplierVehicule
    {
        return SupplierVehicule::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->first(function (SupplierVehicule $supplier) {
                $normalized = preg_replace('/\s+/u', ' ', trim($supplier->name));

                return mb_strtoupper($normalized ?? '', 'UTF-8') === self::SUPPLIER_NAME;
            });
    }
}
