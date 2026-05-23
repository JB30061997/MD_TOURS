<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Planning;
use Illuminate\Http\Request;

class MobileAdminPlanningController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = Planning::query()
            ->with([
                'supplierVehicule',
                'driver',
                'guide',
                'service',
                'destination',
                'vehicule',
                'planningClients.client.supplierClient',
            ]);

        if ($request->filled('status')) {
            if ($request->status === 'today') {
                $query->whereDate('date_du', today());
            } elseif ($request->status === 'tomorrow') {
                $query->whereDate('date_du', today()->addDay());
            } elseif ($request->status === 'upcoming') {
                $query->whereDate('date_du', '>=', today());
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_du', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_du', '<=', $request->date_to);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ref_dossier', 'like', "%{$search}%")
                    ->orWhere('flight', 'like', "%{$search}%")
                    ->orWhere('point_depart', 'like', "%{$search}%")
                    ->orWhere('site', 'like', "%{$search}%")
                    ->orWhereHas('supplierVehicule', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('planningClients.client.supplierClient', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('guide', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('service', fn ($sub) => $sub->where('designation', 'like', "%{$search}%"))
                    ->orWhereHas('destination', fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))
                    ->orWhereHas('vehicule', fn ($sub) => $sub->where('matricule', 'like', "%{$search}%"))
                    ->orWhereHas('planningClients.client', fn ($sub) => $sub->where('full_name', 'like', "%{$search}%"));
            });
        }

        $plannings = $query
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json($plannings);
    }

    public function show(Planning $planning)
    {
        $planning->load([
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client.supplierClient',
        ]);

        return response()->json([
            'planning' => $planning,
        ]);
    }
}
