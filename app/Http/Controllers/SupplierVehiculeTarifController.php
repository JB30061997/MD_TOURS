<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeServiceTarif;
use App\Models\TypeService;
use App\Models\Planning;
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
        $this->tarifSyncer->syncFromPlannings(false);

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
                'financial_years' => $this->supplierFinancialYears($supplier),
                'history' => $this->supplierHistory($supplier),
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

        $result = $this->tarifSyncer->syncFromPlannings($request->boolean('overwrite', true));

        return redirect()
            ->back()
            ->with(
                'success',
                "Synchronisation terminée ({$result['date_from']} → {$result['date_to']}): "
                . "{$result['plannings_analyzed']} planning(s) analysé(s), "
                . "{$result['suppliers_processed']} fournisseur(s) traité(s), "
                . "{$result['created']} tarif(s) créé(s), "
                . "{$result['updated']} mis à jour, "
                . "{$result['unchanged']} inchangé(s), "
                . "{$result['duplicates_ignored']} doublon(s) ignoré(s)."
            );
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

    private function supplierFinancialYears(SupplierVehicule $supplier): array
    {
        $plannings = Planning::with([
            'service:id,designation,type_service',
            'vehicule:id,matricule,marque,modele,nombre_places',
            'supplierClient:id,name',
            'clients:id,full_name',
            'supplierVehiculeInvoicePlannings' => fn ($query) => $query->where('is_selected', true),
            'supplierVehiculeInvoicePlannings.invoice:id,invoice_number,total_amount,period_start,period_end',
        ])
            ->where('supplier_vehicule_id', $supplier->id)
            ->whereNotNull('date_du')
            ->orderByDesc('date_du')
            ->get();

        return $plannings
            ->groupBy(fn ($planning) => optional($planning->date_du)->format('Y'))
            ->sortKeysDesc()
            ->map(function ($yearPlannings, $year) {
                $months = collect(range(1, 12))->mapWithKeys(function ($month) use ($yearPlannings) {
                    $monthPlannings = $yearPlannings->filter(fn ($planning) => (int) $planning->date_du->format('n') === $month);
                    $factured = $monthPlannings->filter(fn ($planning) => $planning->supplierVehiculeInvoicePlannings->isNotEmpty());
                    $notFactured = $monthPlannings->filter(fn ($planning) => $planning->supplierVehiculeInvoicePlannings->isEmpty());
                    $invoiceTotal = $factured
                        ->flatMap(fn ($planning) => $planning->supplierVehiculeInvoicePlannings->pluck('invoice'))
                        ->filter()
                        ->unique('id')
                        ->sum(fn ($invoice) => (float) $invoice->total_amount);

                    $supplierPriceTotal = $monthPlannings->sum(fn ($planning) => (float) $planning->supplier_price);

                    return [
                        (string) $month => [
                            'month' => $month,
                            'label' => Carbon::create(null, $month, 1)->locale('fr')->translatedFormat('F'),
                            'services_count' => $monthPlannings->count(),
                            'budget_total' => round($monthPlannings->sum(fn ($planning) => (float) $planning->budget), 2),
                            'supplier_price_total' => round($supplierPriceTotal, 2),
                            'invoice_total' => round($invoiceTotal, 2),
                            'not_invoiced_total' => round(max($supplierPriceTotal - $invoiceTotal, 0), 2),
                            'factured' => $factured->map(fn ($planning) => $this->planningFinancialRow($planning))->values()->all(),
                            'not_factured' => $notFactured->map(fn ($planning) => $this->planningFinancialRow($planning))->values()->all(),
                        ],
                    ];
                });

                return [
                    'year' => (int) $year,
                    'months' => $months,
                ];
            })
            ->values()
            ->all();
    }

    private function supplierHistory(SupplierVehicule $supplier): array
    {
        $plannings = Planning::with([
            'service:id,designation,type_service',
            'vehicule:id,matricule,marque,modele,nombre_places',
            'supplierClient:id,name',
            'clients:id,full_name',
            'supplierVehiculeInvoicePlannings' => fn ($query) => $query->where('is_selected', true),
            'supplierVehiculeInvoicePlannings.invoice:id,invoice_number,total_amount,period_start,period_end',
        ])
            ->where('supplier_vehicule_id', $supplier->id)
            ->orderByDesc('date_du')
            ->limit(180)
            ->get();

        $invoiceTotal = $plannings
            ->flatMap(fn ($planning) => $planning->supplierVehiculeInvoicePlannings->pluck('invoice'))
            ->filter()
            ->unique('id')
            ->sum(fn ($invoice) => (float) $invoice->total_amount);
        $supplierTotal = $plannings->sum(fn ($planning) => (float) $planning->supplier_price);

        return [
            'summary' => [
                'services_count' => $plannings->count(),
                'last_service_date' => optional($plannings->first()?->date_du)->toDateString(),
                'supplier_price_total' => round($supplierTotal, 2),
                'invoice_total' => round($invoiceTotal, 2),
                'not_invoiced_total' => round(max($supplierTotal - $invoiceTotal, 0), 2),
            ],
            'tarifs' => $supplier->tarifs
                ->sortByDesc('updated_at')
                ->map(fn ($tarif) => [
                    'service' => $tarif->service?->designation ?: '-',
                    'type' => $tarif->typeService?->designation ?: 'Sans type',
                    'vehicle_seats' => $tarif->vehicle_seats,
                    'price' => (float) $tarif->price,
                    'updated_at' => optional($tarif->updated_at)->toDateString(),
                ])
                ->values()
                ->all(),
            'plannings' => $plannings->map(fn ($planning) => $this->planningFinancialRow($planning))->values()->all(),
        ];
    }

    private function planningFinancialRow(Planning $planning): array
    {
        $invoices = $planning->supplierVehiculeInvoicePlannings
            ->pluck('invoice')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'id' => $planning->id,
            'date' => optional($planning->date_du)->toDateString(),
            'reference' => $planning->ref_dossier ?: '-',
            'service' => $planning->service?->designation ?: '-',
            'client' => $planning->clients->pluck('full_name')->filter()->implode(', ')
                ?: ($planning->supplierClient?->name ?: '-'),
            'vehicule' => trim(collect([
                $planning->vehicule?->matricule,
                $planning->vehicule?->marque,
                $planning->vehicule?->modele,
            ])->filter()->implode(' ')) ?: '-',
            'vehicle_seats' => $planning->vehicule?->nombre_places,
            'supplier_price' => round((float) $planning->supplier_price, 2),
            'budget' => round((float) $planning->budget, 2),
            'invoices' => $invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number ?: ('#' . $invoice->id),
                'total_amount' => round((float) $invoice->total_amount, 2),
            ])->all(),
        ];
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
