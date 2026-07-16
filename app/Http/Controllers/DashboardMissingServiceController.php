<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPlanningServiceRequest;
use App\Http\Requests\ListMissingServicePlanningsRequest;
use App\Models\Client;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardMissingServiceController extends Controller
{
    public function index(ListMissingServicePlanningsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Planning::query()
            ->whereNull('service_id')
            ->with([
                'driver:id,name',
                'vehicule:id,matricule,marque,modele',
                'destination:id,name,city',
                'planningClients.client:id,full_name',
            ])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date_du', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date_du', '<=', $date))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('date_du', $date))
            ->when($filters['driver_id'] ?? null, fn ($q, $id) => $q->where('driver_id', $id))
            ->when($filters['destination_id'] ?? null, fn ($q, $id) => $q->where('destination_id', $id))
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
            'clients' => $planning->planningClients->map(fn ($item) => $item->client?->full_name)->filter()->values()->join(', ') ?: '-',
            'driver' => $planning->driver?->name ?: 'Sans chauffeur',
            'vehicle' => trim(implode(' ', array_filter([$planning->vehicule?->matricule, $planning->vehicule?->marque, $planning->vehicule?->modele]))) ?: '-',
            'destination' => trim(implode(' - ', array_filter([$planning->destination?->name, $planning->destination?->city]))) ?: '-',
            'service' => 'Sans service',
        ]);

        return response()->json([
            'plannings' => $plannings,
            'options' => [
                'services' => Service::query()->orderBy('designation')->get(['id', 'designation']),
                'drivers' => Driver::query()->orderBy('name')->get(['id', 'name']),
                'clients' => Client::query()->orderBy('full_name')->get(['id', 'full_name']),
                'destinations' => Destination::query()->orderBy('name')->get(['id', 'name', 'city']),
            ],
        ]);
    }

    public function assign(AssignPlanningServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $requestedIds = collect($data['planning_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $result = DB::transaction(function () use ($requestedIds, $data) {
            $plannings = Planning::query()->whereIn('id', $requestedIds)->lockForUpdate()->get(['id', 'service_id']);
            $alreadyAssigned = $plannings->whereNotNull('service_id')->pluck('id')->values();
            $assignableIds = $plannings->whereNull('service_id')->pluck('id')->values();
            $updated = Planning::query()
                ->whereIn('id', $assignableIds)
                ->whereNull('service_id')
                ->update(['service_id' => $data['service_id']]);

            return [
                'updated' => $updated,
                'skipped' => $alreadyAssigned->count() + max($assignableIds->count() - $updated, 0),
                'already_assigned_ids' => $alreadyAssigned,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => sprintf('%d planning(s) affecté(s).%s', $result['updated'], $result['skipped'] ? ' '.$result['skipped'].' planning(s) déjà affecté(s) ont été ignoré(s).' : ''),
            ...$result,
            'remaining' => Planning::query()->whereNull('service_id')->count(),
        ]);
    }
}
