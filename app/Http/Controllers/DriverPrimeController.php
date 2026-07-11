<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverPrimeFilterRequest;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\TypeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DriverPrimeController extends Controller
{
    public function index(DriverPrimeFilterRequest $request): Response
    {
        $this->authorizeAccess($request);
        $filters = $this->filters($request);
        $rows = empty($filters['type_service_ids'])
            ? collect()
            : $this->filteredPlannings($filters)->get()->map(fn (Planning $planning) => $this->formatRow($planning));

        return Inertia::render('DriverPrimes/Index', [
            'typeServices' => TypeService::query()->orderBy('designation')->get(['id', 'designation']),
            'filters' => $filters,
            'rows' => $rows->values(),
            'summary' => $this->summary($rows),
        ]);
    }

    public function pdf(DriverPrimeFilterRequest $request)
    {
        $this->authorizeAccess($request);
        $filters = $this->filters($request);
        $rows = $this->filteredPlannings($filters)->get()->map(fn (Planning $planning) => $this->formatRow($planning));
        $typeServices = TypeService::query()
            ->whereIn('id', $filters['type_service_ids'])
            ->orderBy('designation')
            ->pluck('designation');

        $groups = $rows
            ->groupBy(fn (array $row) => $row['driver_id'] ? 'driver-'.$row['driver_id'] : 'without-driver')
            ->map(fn (Collection $driverRows) => [
                'driver' => $driverRows->first()['driver'],
                'rows' => $driverRows->values(),
                'days' => $driverRows->pluck('date_iso')->filter()->unique()->count(),
                'references' => $driverRows->pluck('reference')->filter(fn ($value) => $value !== '-')->unique()->count(),
                'services' => $driverRows->count(),
            ])->values();

        return Pdf::loadView('pdf.driver-primes', [
            'groups' => $groups,
            'summary' => $this->summary($rows),
            'typeServices' => $typeServices,
            'filters' => $filters,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('primes-chauffeurs-'.now()->format('Ymd-His').'.pdf');
    }

    private function filters(DriverPrimeFilterRequest $request): array
    {
        $validated = $request->validated();

        return [
            'type_service_ids' => collect($validated['type_service_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'date_from' => $validated['date_from'] ?? now()->startOfMonth()->toDateString(),
            'date_to' => $validated['date_to'] ?? now()->endOfMonth()->toDateString(),
        ];
    }

    private function filteredPlannings(array $filters): Builder
    {
        return Planning::query()
            ->with([
                'driver:id,name,phone,email',
                'service.typeService:id,designation',
                'supplierVehicule:id,name',
                'supplierClient:id,name',
                'guide:id,name',
                'vehicule:id,matricule,marque,modele,nombre_places',
                'destination:id,name',
                'planningClients.client:id,full_name',
            ])
            ->whereDate('date_du', '>=', $filters['date_from'])
            ->whereDate('date_du', '<=', $filters['date_to'])
            ->whereHas('service.typeService', fn (Builder $query) => $query->whereIn('type_services.id', $filters['type_service_ids']))
            ->orderByRaw('driver_id IS NULL')
            ->orderBy(Driver::query()->select('name')->whereColumn('drivers.id', 'plannings.driver_id'))
            ->orderBy('date_du')
            ->orderBy('heure')
            ->orderBy('ref_dossier');
    }

    private function summary(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'drivers_count' => $rows->pluck('driver_id')->filter()->unique()->count(),
            'without_driver' => $rows->whereNull('driver_id')->count(),
            'unique_days' => $rows->pluck('date_iso')->filter()->unique()->count(),
            'unique_references' => $rows->pluck('reference')->filter(fn ($value) => $value !== '-')->unique()->count(),
        ];
    }

    private function authorizeAccess(DriverPrimeFilterRequest $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->can('driver-primes.view'))) {
            abort(403, 'Vous n’avez pas la permission de consulter les primes chauffeurs.');
        }
    }

    private function formatRow(Planning $planning): array
    {
        $clients = $planning->planningClients->map(fn ($item) => $item->client?->full_name)->filter()->values()->all();
        $vehicle = collect([$planning->vehicule?->matricule, $planning->vehicule?->marque, $planning->vehicule?->modele])->filter()->implode(' ');

        return [
            'id' => $planning->id,
            'driver_id' => $planning->driver_id,
            'driver' => $planning->driver?->name ?: 'Sans chauffeur',
            'date' => $planning->date_du?->format('d/m/Y') ?: '-',
            'date_iso' => $planning->date_du?->toDateString(),
            'time' => $planning->heure?->format('H:i') ?: '-',
            'reference' => $planning->ref_dossier ?: '-',
            'service' => $planning->service?->designation ?: '-',
            'service_type' => $planning->service?->typeService?->designation ?: '-',
            'start_point' => $planning->point_depart ?: '-',
            'destination' => $planning->destination?->name ?: ($planning->site ?: '-'),
            'vehicle' => $vehicle ?: '-',
            'pax' => $planning->nbr_personnes ?: '-',
            'clients' => $clients,
            'supplier_vehicle' => $planning->supplierVehicule?->name ?: '-',
            'supplier_client' => $planning->supplierClient?->name ?: '-',
            'guide' => $planning->guide?->name ?: '-',
        ];
    }

    private function logoDataUri(): ?string
    {
        $path = resource_path('js/assets/images/logo_md_tours.png');
        return is_file($path) ? 'data:image/png;base64,'.base64_encode(file_get_contents($path)) : null;
    }
}
