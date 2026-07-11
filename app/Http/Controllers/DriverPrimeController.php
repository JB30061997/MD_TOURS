<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Planning;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverPrimeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $driverId = $request->input('driver_id');
        $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->input('date_to') ?: now()->endOfMonth()->toDateString();

        $rows = collect();
        $selectedDriver = null;

        if ($driverId && $dateFrom && $dateTo) {
            $selectedDriver = Driver::find($driverId);

            $rows = Planning::query()
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
                ->where('driver_id', $driverId)
                ->whereDate('date_du', '>=', $dateFrom)
                ->whereDate('date_du', '<=', $dateTo)
                ->whereHas('service.typeService', fn ($query) => $query->whereRaw('LOWER(designation) = ?', ['circuit']))
                ->orderBy('date_du')
                ->orderBy('heure')
                ->orderBy('ref_dossier')
                ->get()
                ->map(fn (Planning $planning) => $this->formatRow($planning));
        }

        return Inertia::render('DriverPrimes/Index', [
            'drivers' => Driver::query()
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'status']),
            'filters' => [
                'driver_id' => $driverId ?: '',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'selectedDriver' => $selectedDriver,
            'rows' => $rows->values(),
            'summary' => [
                'count' => $rows->count(),
                'unique_days' => $rows->pluck('date')->filter()->unique()->count(),
                'unique_references' => $rows->pluck('reference')->filter(fn ($value) => $value && $value !== '-')->unique()->count(),
            ],
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        if (!$user || (!$user->isSuperAdmin() && !$user->can('driver-primes.view'))) {
            abort(403, 'Vous n’avez pas la permission de consulter les primes chauffeurs.');
        }
    }

    private function formatRow(Planning $planning): array
    {
        $clients = $planning->planningClients
            ->map(fn ($item) => $item->client?->full_name)
            ->filter()
            ->values()
            ->all();

        $vehicle = collect([
            $planning->vehicule?->matricule,
            $planning->vehicule?->marque,
            $planning->vehicule?->modele,
        ])->filter()->implode(' ');

        return [
            'id' => $planning->id,
            'date' => $planning->date_du?->format('d/m/Y') ?: '-',
            'date_end' => $planning->date_au?->format('d/m/Y') ?: '-',
            'time' => $planning->heure ? $planning->heure->format('H:i') : '-',
            'reference' => $planning->ref_dossier ?: '-',
            'service' => $planning->service?->designation ?: '-',
            'service_type' => $planning->service?->typeService?->designation ?: '-',
            'start_point' => $planning->point_depart ?: '-',
            'destination' => $planning->destination?->name ?: ($planning->site ?: '-'),
            'vehicle' => $vehicle ?: '-',
            'vehicle_places' => $planning->vehicule?->nombre_places ?: '-',
            'supplier_vehicle' => $planning->supplierVehicule?->name ?: '-',
            'supplier_client' => $planning->supplierClient?->name ?: '-',
            'guide' => $planning->guide?->name ?: '-',
            'pax' => $planning->nbr_personnes ?: '-',
            'clients' => $clients,
            'notes' => $planning->notes ?: '',
        ];
    }

}
