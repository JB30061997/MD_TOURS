<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeServiceTarif;
use App\Models\TypeService;
use App\Services\SupplierVehiculeTarifSyncer;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class SupplierVehiculeTarifController extends Controller
{
    private const VEHICLE_SEAT_CATEGORIES = [7, 17, 36, 40, 48];

    public function __construct(private readonly SupplierVehiculeTarifSyncer $tarifSyncer)
    {
    }

    public function index(Request $request)
    {
        $this->ensureTarifsTableExists();
        $this->ensureContractualTypeServicesExist();
        $this->tarifSyncer->syncFromPlannings();

        $supplierSearch = $request->string('supplier_search')->toString();

        $supplierVehicules = SupplierVehicule::query()
            ->with(['tarifs.service:id,designation,type_service', 'tarifs.typeService:id,designation'])
            ->withCount('tarifs')
            ->withMax('tarifs', 'updated_at')
            ->when($supplierSearch, function ($query) use ($supplierSearch) {
                $query->where(function ($q) use ($supplierSearch) {
                    $q->where('name', 'like', "%{$supplierSearch}%")
                        ->orWhere('email', 'like', "%{$supplierSearch}%")
                        ->orWhere('phone', 'like', "%{$supplierSearch}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (SupplierVehicule $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'tarifs_count' => $supplier->tarifs_count,
                'configured_services_count' => $supplier->tarifs
                    ->filter(fn ($tarif) => (float) $tarif->price > 0)
                    ->map(fn ($tarif) => $this->tarifRowKey($tarif->service_id, $tarif->type_service_id))
                    ->unique()
                    ->count(),
                'latest_tarif_update' => $supplier->tarifs_max_updated_at
                    ? Carbon::parse($supplier->tarifs_max_updated_at)->toISOString()
                    : null,
                'tarif_rows' => $this->supplierTarifRows($supplier),
            ]);

        return Inertia::render('SupplierVehiculeTarifs/Index', [
            'supplierVehicules' => $supplierVehicules,
            'services' => Service::with('typeService:id,designation')
                ->orderBy('designation')
                ->get(['id', 'designation', 'type_service']),
            'typeServices' => TypeService::orderBy('designation')->get(['id', 'designation']),
            'seatCategories' => self::VEHICLE_SEAT_CATEGORIES,
            'filters' => [
                'supplier_search' => $supplierSearch,
            ],
        ]);
    }

    public function updateSupplierTarifs(Request $request, SupplierVehicule $supplierVehicule)
    {
        $this->ensureTarifsTableExists();

        $seatCategories = implode(',', self::VEHICLE_SEAT_CATEGORIES);
        $data = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.service_id' => ['required', 'exists:services,id'],
            'rows.*.type_service_id' => ['nullable', 'exists:type_services,id'],
            'rows.*.prices' => ['nullable', 'array'],
            'rows.*.prices.*' => ['nullable', 'numeric', 'min:0'],
            'rows.*.vehicle_seats' => ['nullable', 'array'],
            'rows.*.vehicle_seats.*' => ["nullable", "integer", "in:{$seatCategories}"],
        ]);

        DB::transaction(function () use ($supplierVehicule, $data) {
            SupplierVehiculeServiceTarif::where('supplier_vehicule_id', $supplierVehicule->id)->delete();

            foreach ($data['rows'] ?? [] as $row) {
                foreach (self::VEHICLE_SEAT_CATEGORIES as $seats) {
                    $price = $row['prices'][(string) $seats] ?? null;

                    if ($price === null || $price === '') {
                        continue;
                    }

                    SupplierVehiculeServiceTarif::updateOrCreate(
                        [
                            'supplier_vehicule_id' => $supplierVehicule->id,
                            'service_id' => $row['service_id'],
                            'type_service_id' => $row['type_service_id'] ?: null,
                            'vehicle_seats' => $seats,
                        ],
                        [
                            'price' => round((float) $price, 2),
                        ]
                    );
                }
            }
        });

        return redirect()
            ->back()
            ->with('success', "Tarifs de {$supplierVehicule->name} enregistrés avec succès.");
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
        if (!Schema::hasTable('supplier_vehicule_service_tarifs')) {
            Schema::create('supplier_vehicule_service_tarifs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_vehicule_id')
                    ->constrained('supplier_vehicules')
                    ->cascadeOnDelete();
                $table->foreignId('service_id')
                    ->constrained('services')
                    ->cascadeOnDelete();
                $table->foreignId('type_service_id')
                    ->nullable()
                    ->constrained('type_services')
                    ->nullOnDelete();
                $table->unsignedInteger('vehicle_seats')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->timestamps();
                $table->unique(
                    ['supplier_vehicule_id', 'service_id', 'type_service_id', 'vehicle_seats'],
                    'supplier_service_type_seats_tarifs_unique'
                );
            });

            return;
        }

        Schema::table('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_vehicule_service_tarifs', 'type_service_id')) {
                $table->foreignId('type_service_id')
                    ->nullable()
                    ->after('service_id')
                    ->constrained('type_services')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('supplier_vehicule_service_tarifs', 'vehicle_seats')) {
                $table->unsignedInteger('vehicle_seats')->nullable()->after('type_service_id');
            }
        });

        $this->ensureTarifIndexesAreCurrent();
    }

    private function supplierTarifRows(SupplierVehicule $supplier): array
    {
        return $supplier->tarifs
            ->groupBy(fn ($tarif) => $this->tarifRowKey($tarif->service_id, $tarif->type_service_id))
            ->map(function ($tarifs) {
                $first = $tarifs->first();
                $prices = collect(self::VEHICLE_SEAT_CATEGORIES)
                    ->mapWithKeys(fn ($seats) => [
                        (string) $seats => optional($tarifs->firstWhere('vehicle_seats', $seats))->price,
                    ])
                    ->all();

                return [
                    'service_id' => $first->service_id,
                    'service_name' => $first->service?->designation,
                    'type_service_id' => $first->type_service_id,
                    'type_service_name' => $first->typeService?->designation ?? 'Sans type',
                    'prices' => $prices,
                ];
            })
            ->values()
            ->all();
    }

    private function tarifRowKey(int|string|null $serviceId, int|string|null $typeServiceId): string
    {
        return ($serviceId ?: 'none') . ':' . ($typeServiceId ?: 'none');
    }

    private function ensureLegacyUniqueIndexDropped(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addIndexIfMissing('supplier_tarifs_supplier_idx', ['supplier_vehicule_id']);
        $this->addIndexIfMissing('supplier_tarifs_service_idx', ['service_id']);

        $exists = collect(DB::select(
            'SHOW INDEX FROM supplier_vehicule_service_tarifs WHERE Key_name = ?',
            ['supplier_service_tarifs_unique']
        ))->isNotEmpty();

        if ($exists) {
            DB::statement('ALTER TABLE supplier_vehicule_service_tarifs DROP INDEX supplier_service_tarifs_unique');
        }
    }

    private function addIndexIfMissing(string $index, array $columns): void
    {
        $exists = collect(DB::select(
            'SHOW INDEX FROM supplier_vehicule_service_tarifs WHERE Key_name = ?',
            [$index]
        ))->isNotEmpty();

        if (!$exists) {
            DB::statement('ALTER TABLE supplier_vehicule_service_tarifs ADD INDEX ' . $index . ' (' . implode(', ', $columns) . ')');
        }
    }

    private function ensureNewUniqueIndexExists(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = collect(DB::select(
            'SHOW INDEX FROM supplier_vehicule_service_tarifs WHERE Key_name = ?',
            ['supplier_service_type_seats_tarifs_unique']
        ))->isNotEmpty();

        if (!$exists) {
            DB::statement('ALTER TABLE supplier_vehicule_service_tarifs ADD UNIQUE supplier_service_type_seats_tarifs_unique (supplier_vehicule_id, service_id, type_service_id, vehicle_seats)');
        }
    }

    private function ensureTarifIndexesAreCurrent(): void
    {
        if (!Schema::hasTable('supplier_vehicule_service_tarifs')) {
            return;
        }

        $this->ensureLegacyUniqueIndexDropped();
        $this->ensureNewUniqueIndexExists();
    }

    private function ensureContractualTypeServicesExist(): void
    {
        foreach ([
            'Circuit',
            'Transfer',
            'Transfert aéroport - hôtel - aéroport',
            'Transfert inter-ville',
            'Excursion',
        ] as $designation) {
            TypeService::firstOrCreate(['designation' => $designation]);
        }
    }
}
