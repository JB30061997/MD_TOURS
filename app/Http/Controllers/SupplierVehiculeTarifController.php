<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeServiceTarif;
use App\Models\TypeService;
use App\Services\SupplierVehiculeTarifSyncer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class SupplierVehiculeTarifController extends Controller
{
    public function __construct(private readonly SupplierVehiculeTarifSyncer $tarifSyncer)
    {
    }

    public function index(Request $request)
    {
        $this->ensureTarifsTableExists();
        $this->ensureContractualTypeServicesExist();
        $this->tarifSyncer->syncFromPlannings();

        $serviceSearch = $request->string('service_search')->toString();
        $supplierSearch = $request->string('supplier_search')->toString();
        $perPage = (int) $request->input('per_page', 12);
        $perPage = in_array($perPage, [10, 12, 20, 30], true) ? $perPage : 12;

        $services = Service::with('typeService')
            ->when($serviceSearch, function ($query) use ($serviceSearch) {
                $query->where(function ($q) use ($serviceSearch) {
                    $q->where('designation', 'like', "%{$serviceSearch}%")
                        ->orWhereHas('typeService', function ($typeQuery) use ($serviceSearch) {
                            $typeQuery->where('designation', 'like', "%{$serviceSearch}%");
                        });
                });
            })
            ->orderBy('designation')
            ->paginate($perPage)
            ->withQueryString();

        $supplierVehicules = SupplierVehicule::query()
            ->when($supplierSearch, function ($query) use ($supplierSearch) {
                $query->where(function ($q) use ($supplierSearch) {
                    $q->where('name', 'like', "%{$supplierSearch}%")
                        ->orWhere('email', 'like', "%{$supplierSearch}%")
                        ->orWhere('phone', 'like', "%{$supplierSearch}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $tarifs = SupplierVehiculeServiceTarif::query()
            ->whereIn('service_id', $services->pluck('id'))
            ->whereIn('supplier_vehicule_id', $supplierVehicules->pluck('id'))
            ->get(['service_id', 'supplier_vehicule_id', 'price'])
            ->mapWithKeys(fn ($tarif) => [
                $this->tarifKey($tarif->service_id, $tarif->supplier_vehicule_id) => $tarif->price,
            ]);

        return Inertia::render('SupplierVehiculeTarifs/Index', [
            'services' => $services,
            'supplierVehicules' => $supplierVehicules,
            'tarifs' => $tarifs,
            'typeServices' => TypeService::orderBy('designation')->get(['id', 'designation']),
            'filters' => [
                'service_search' => $serviceSearch,
                'supplier_search' => $supplierSearch,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function updateMatrix(Request $request)
    {
        $this->ensureTarifsTableExists();

        $data = $request->validate([
            'tarifs' => ['required', 'array'],
            'tarifs.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['tarifs'] as $key => $price) {
                [$serviceId, $supplierVehiculeId] = $this->parseTarifKey($key);

                if (!$serviceId || !$supplierVehiculeId) {
                    continue;
                }

                $exists = Service::whereKey($serviceId)->exists()
                    && SupplierVehicule::whereKey($supplierVehiculeId)->exists();

                if (!$exists) {
                    continue;
                }

                if ($price === null || $price === '') {
                    SupplierVehiculeServiceTarif::where('service_id', $serviceId)
                        ->where('supplier_vehicule_id', $supplierVehiculeId)
                        ->delete();

                    continue;
                }

                SupplierVehiculeServiceTarif::updateOrCreate(
                    [
                        'service_id' => $serviceId,
                        'supplier_vehicule_id' => $supplierVehiculeId,
                    ],
                    [
                        'price' => round((float) $price, 2),
                    ]
                );
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Tarifs fournisseurs enregistrés avec succès.');
    }

    public function syncFromPlannings(Request $request)
    {
        $this->ensureTarifsTableExists();

        $result = $this->tarifSyncer->syncFromPlannings((bool) $request->boolean('overwrite'));

        return redirect()
            ->back()
            ->with('success', "Synchronisation terminée: {$result['created']} tarif(s) créé(s), {$result['updated']} tarif(s) mis à jour, {$result['skipped']} conservé(s).");
    }

    public function storeService(Request $request)
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'type_service' => ['nullable', 'exists:type_services,id'],
        ]);

        Service::create($data);

        return redirect()
            ->back()
            ->with('success', 'Service ajouté avec succès.');
    }

    public function updateService(Request $request, Service $service)
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'type_service' => ['nullable', 'exists:type_services,id'],
        ]);

        $service->update($data);

        return redirect()
            ->back()
            ->with('success', 'Service modifié avec succès.');
    }

    public function destroyService(Service $service)
    {
        $service->delete();

        return redirect()
            ->back()
            ->with('success', 'Service supprimé avec succès.');
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        SupplierVehicule::create([
            ...$data,
            'is_active' => true,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Fournisseur ajouté avec succès.');
    }

    private function tarifKey(int|string $serviceId, int|string $supplierVehiculeId): string
    {
        return "{$serviceId}:{$supplierVehiculeId}";
    }

    private function parseTarifKey(string|int $key): array
    {
        if (!is_string($key) || !str_contains($key, ':')) {
            return [null, null];
        }

        [$serviceId, $supplierVehiculeId] = explode(':', $key, 2);

        return [(int) $serviceId, (int) $supplierVehiculeId];
    }

    public function ensureTarifsTableExists(): void
    {
        if (Schema::hasTable('supplier_vehicule_service_tarifs')) {
            return;
        }

        Schema::create('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_vehicule_id')
                ->constrained('supplier_vehicules')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(
                ['supplier_vehicule_id', 'service_id'],
                'supplier_service_tarifs_unique'
            );
        });
    }

    private function ensureContractualTypeServicesExist(): void
    {
        foreach ([
            'Circuit',
            'Transfert aéroport - hôtel - aéroport',
            'Transfert inter-ville',
            'Excursion',
        ] as $designation) {
            TypeService::firstOrCreate(['designation' => $designation]);
        }
    }
}
