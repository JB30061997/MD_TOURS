<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPlanningSupplierVehiculeRequest;
use App\Http\Requests\ListMissingSupplierPlanningsRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Services\TemporaryMdToursSupplierAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardMissingSupplierController extends Controller
{
    public function index(ListMissingSupplierPlanningsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Planning::query()
            ->whereNull('supplier_vehicule_id')
            ->with([
                'service:id,designation,type_service',
                'service.typeService:id,designation',
                'driver:id,name',
                'vehicule:id,matricule,marque,modele',
                'destination:id,name,city',
                'planningClients.client:id,full_name',
            ])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date_du', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date_du', '<=', $date))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('date_du', $date))
            ->when($filters['service_id'] ?? null, fn ($q, $id) => $q->where('service_id', $id))
            ->when($filters['driver_id'] ?? null, fn ($q, $id) => $q->where('driver_id', $id))
            ->when($filters['client_id'] ?? null, function ($q, $id) {
                $q->whereHas('planningClients', fn ($clientQuery) => $clientQuery->where('client_id', $id));
            })
            ->when($filters['search'] ?? null, function ($q, string $search) {
                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('ref_dossier', 'like', '%'.$search.'%');

                    if (ctype_digit($search)) {
                        $searchQuery->orWhereKey((int) $search);
                    }
                });
            })
            ->orderBy('date_du')
            ->orderBy('heure')
            ->orderBy('id');

        $plannings = $query->paginate(50)->withQueryString();
        $plannings->through(fn (Planning $planning) => [
            'id' => $planning->id,
            'reference' => $planning->ref_dossier ?: '-',
            'planning_number' => '#'.$planning->id,
            'date' => $planning->date_du?->format('d/m/Y') ?: '-',
            'date_raw' => $planning->date_du?->toDateString(),
            'service' => $planning->service?->designation ?: 'Sans service',
            'service_type' => $planning->service?->typeService?->designation ?: '-',
            'clients' => $planning->planningClients
                ->map(fn ($planningClient) => $planningClient->client?->full_name)
                ->filter()
                ->values()
                ->join(', ') ?: '-',
            'driver' => $planning->driver?->name ?: 'Sans chauffeur',
            'vehicle' => trim(implode(' ', array_filter([
                $planning->vehicule?->matricule,
                $planning->vehicule?->marque,
                $planning->vehicule?->modele,
            ]))) ?: '-',
            'destination' => trim(implode(' - ', array_filter([
                $planning->destination?->name,
                $planning->destination?->city,
            ]))) ?: '-',
            'supplier_vehicle' => 'Sans fournisseur véhicule',
        ]);

        return response()->json([
            'plannings' => $plannings,
            'options' => [
                'suppliers' => SupplierVehicule::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'services' => Service::query()->orderBy('designation')->get(['id', 'designation']),
                'drivers' => Driver::query()->orderBy('name')->get(['id', 'name']),
                'clients' => Client::query()->orderBy('full_name')->get(['id', 'full_name']),
            ],
        ]);
    }

    public function autoAssign(Request $request, TemporaryMdToursSupplierAssignmentService $service): JsonResponse
    {
        $this->ensureAdministrator($request);
        $result = $service->run();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function assign(AssignPlanningSupplierVehiculeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $requestedIds = collect($data['planning_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $result = DB::transaction(function () use ($requestedIds, $data) {
            $plannings = Planning::query()
                ->whereIn('id', $requestedIds)
                ->lockForUpdate()
                ->get(['id', 'supplier_vehicule_id']);

            $alreadyAssigned = $plannings
                ->whereNotNull('supplier_vehicule_id')
                ->pluck('id')
                ->values();
            $assignableIds = $plannings
                ->whereNull('supplier_vehicule_id')
                ->pluck('id')
                ->values();

            $updated = Planning::query()
                ->whereIn('id', $assignableIds)
                ->whereNull('supplier_vehicule_id')
                ->update(['supplier_vehicule_id' => $data['supplier_vehicule_id']]);

            return [
                'updated' => $updated,
                'skipped' => $alreadyAssigned->count() + max($assignableIds->count() - $updated, 0),
                'already_assigned_ids' => $alreadyAssigned,
            ];
        });

        $remaining = Planning::query()->whereNull('supplier_vehicule_id')->count();

        return response()->json([
            'success' => true,
            'message' => sprintf(
                '%d planning(s) affecté(s).%s',
                $result['updated'],
                $result['skipped'] > 0 ? ' '.$result['skipped'].' planning(s) déjà affecté(s) ont été ignoré(s).' : ''
            ),
            ...$result,
            'remaining' => $remaining,
        ]);
    }

    private function ensureAdministrator(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin() === true, 403, 'Cette action est réservée aux administrateurs.');
    }
}
