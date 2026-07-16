<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Planning;
use App\Models\Service;
use App\Models\TypeService;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $statsFilters = $request->validate([
            'stats_date_from' => ['nullable', 'date'],
            'stats_date_to' => ['nullable', 'date', 'after_or_equal:stats_date_from'],
            'stats_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'stats_destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
        ]);
        $statsDateFrom = $statsFilters['stats_date_from'] ?? now()->startOfMonth()->toDateString();
        $statsDateTo = $statsFilters['stats_date_to'] ?? now()->endOfMonth()->toDateString();
        $statsServiceId = $statsFilters['stats_service_id'] ?? null;
        $statsDestinationId = $statsFilters['stats_destination_id'] ?? null;
        $services = Service::with('typeService')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('designation', 'like', "%{$search}%");
                    $q->orWhere('description', 'like', "%{$search}%");

                    $q->orWhereHas('typeService', function ($typeQuery) use ($search) {
                        $typeQuery->where('designation', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $planningStatsQuery = Planning::query()
            ->whereBetween('date_du', [$statsDateFrom, $statsDateTo])
            ->when($statsServiceId, fn ($query, $id) => $query->where('service_id', $id))
            ->when($statsDestinationId, fn ($query, $id) => $query->where('destination_id', $id));

        $serviceDestinationStats = (clone $planningStatsQuery)
            ->leftJoin('services', 'services.id', '=', 'plannings.service_id')
            ->leftJoin('destinations', 'destinations.id', '=', 'plannings.destination_id')
            ->selectRaw('
                plannings.service_id,
                plannings.destination_id,
                COALESCE(services.designation, "Sans service") as service_name,
                COALESCE(destinations.name, "Sans destination") as destination_name,
                COALESCE(destinations.city, "-") as destination_city,
                COUNT(plannings.id) as total_trips,
                COUNT(DISTINCT plannings.ref_dossier) as total_dossiers,
                COALESCE(SUM(plannings.budget), 0) as total_budget,
                COALESCE(SUM(plannings.supplier_price), 0) as total_supplier_price
            ')
            ->groupBy(
                'plannings.service_id',
                'plannings.destination_id',
                'services.designation',
                'destinations.name',
                'destinations.city'
            )
            ->orderByDesc('total_trips')
            ->get()
            ->map(fn ($row) => [
                'service_id' => $row->service_id,
                'destination_id' => $row->destination_id,
                'service_name' => $row->service_name,
                'destination_name' => $row->destination_name,
                'destination_city' => $row->destination_city,
                'total_trips' => (int) $row->total_trips,
                'total_dossiers' => (int) $row->total_dossiers,
                'total_budget' => round((float) $row->total_budget, 2),
                'total_supplier_price' => round((float) $row->total_supplier_price, 2),
                'gross_margin' => round((float) $row->total_budget - (float) $row->total_supplier_price, 2),
            ])
            ->values();

        $statsSummary = [
            'total_trips' => (clone $planningStatsQuery)->count(),
            'services_count' => (clone $planningStatsQuery)->whereNotNull('service_id')->distinct()->count('service_id'),
            'destinations_count' => (clone $planningStatsQuery)->whereNotNull('destination_id')->distinct()->count('destination_id'),
            'total_budget' => round((float) (clone $planningStatsQuery)->sum('budget'), 2),
        ];

        $services->getCollection()->transform(fn (Service $service) => [
            'id' => $service->id,
            'designation' => $service->designation,
            'type_service_id' => $service->type_service,
            'type_service_name' => $service->typeService?->designation,
            'description' => $service->description,
        ]);

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $search,
                'stats_date_from' => $statsDateFrom,
                'stats_date_to' => $statsDateTo,
                'stats_service_id' => $statsServiceId ?: '',
                'stats_destination_id' => $statsDestinationId ?: '',
            ],
            'allServices' => Service::with('typeService')
                ->orderBy('designation')
                ->get(['id', 'designation', 'type_service'])
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'designation' => $service->designation,
                    'type_service_id' => $service->type_service,
                    'type_service_name' => $service->typeService?->designation,
                ])
                ->values(),
            'statsDestinations' => Destination::query()
                ->orderBy('city')
                ->orderBy('name')
                ->get(['id', 'name', 'city']),
            'serviceDestinationStats' => $serviceDestinationStats,
            'statsSummary' => $statsSummary,
        ]);
    }

    public function create()
    {
        return Inertia::render('Services/Create', [
            'typeServices' => TypeService::orderBy('designation')->get()
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
        ];

        $rules['description'] = ['nullable', 'string', 'max:5000'];

        $data = $request->validate($rules);

        $service = Service::create($data);

        return redirect()->route('services.index')->with([
            'success' => 'Service ajouté avec succès',
            'lastCreatedService' => $service->load('typeService'),
        ]);
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        return Inertia::render('Services/Edit', [
            'service' => [
                'id' => $service->id,
                'designation' => $service->designation,
                'type_service' => $service->type_service,
                'description' => $service->description,
            ],
            'typeServices' => TypeService::orderBy('designation')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
        ];

        $rules['description'] = ['nullable', 'string', 'max:5000'];

        $data = $request->validate($rules);

        $service = Service::findOrFail($id);
        $service->update($data);

        return redirect()->route('services.index')
            ->with('success', 'Service modifié avec succès');
    }

    public function bulkReplace(Request $request)
    {
        $data = $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'replacement_service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $replacementId = (int) $data['replacement_service_id'];
        $serviceIds = collect($data['service_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $replacementId)
            ->values();

        if ($serviceIds->isEmpty()) {
            return redirect()->back()->with('error', 'Choisissez au moins un service différent du service de remplacement.');
        }

        try {
            $summary = DB::transaction(function () use ($serviceIds, $replacementId) {
                $ids = $serviceIds->all();

                $planningCount = DB::table('plannings')
                    ->whereIn('service_id', $ids)
                    ->update(['service_id' => $replacementId]);

                $commandeCount = Schema::hasTable('commandes')
                    ? DB::table('commandes')->whereIn('service_id', $ids)->update(['service_id' => $replacementId])
                    : 0;

                $tarifCount = 0;

                if (Schema::hasTable('supplier_vehicule_service_tarifs')) {
                    $tarifs = DB::table('supplier_vehicule_service_tarifs')
                        ->whereIn('service_id', $ids)
                        ->get();

                    foreach ($tarifs as $tarif) {
                        $duplicate = DB::table('supplier_vehicule_service_tarifs')
                            ->where('supplier_vehicule_id', $tarif->supplier_vehicule_id)
                            ->where('service_id', $replacementId)
                            ->where('type_service_id', $tarif->type_service_id)
                            ->where('vehicle_seats', $tarif->vehicle_seats)
                            ->exists();

                        if ($duplicate) {
                            DB::table('supplier_vehicule_service_tarifs')->where('id', $tarif->id)->delete();
                        } else {
                            DB::table('supplier_vehicule_service_tarifs')->where('id', $tarif->id)->update([
                                'service_id' => $replacementId,
                                'updated_at' => now(),
                            ]);
                        }

                        $tarifCount++;
                    }
                }

                $deletedCount = Service::whereIn('id', $ids)->delete();

                return compact('planningCount', 'commandeCount', 'tarifCount', 'deletedCount');
            });
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ces services', $exception));
        }

        return redirect()->back()->with(
            'success',
            "Remplacement terminé: {$summary['deletedCount']} service(s), {$summary['planningCount']} planning(s), {$summary['commandeCount']} bon(s), {$summary['tarifCount']} tarif(s)."
        );
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $message = DeleteProtection::blockingMessage('ce service', [
            ['table' => 'plannings', 'column' => 'service_id', 'value' => $service->id, 'label' => 'planning(s)'],
            ['table' => 'commandes', 'column' => 'service_id', 'value' => $service->id, 'label' => 'bon(s) de commande'],
            ['table' => 'supplier_vehicule_service_tarifs', 'column' => 'service_id', 'value' => $service->id, 'label' => 'tarif(s) fournisseur'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $service->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ce service', $exception));
        }

        return redirect()->back()
            ->with('success', 'Service supprimé avec succès');
    }
}
