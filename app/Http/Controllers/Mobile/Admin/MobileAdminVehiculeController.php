<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicule;
use App\Models\VehicleMaintenance;
use Illuminate\Http\Request;

class MobileAdminVehiculeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $vehicules = Vehicule::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('matricule', 'like', "%{$search}%")
                        ->orWhere('marque', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) $request->get('per_page', 10));

        return response()->json($vehicules);
    }

    public function show(Vehicule $vehicule)
    {
        $vehicule->load([
            'maintenances' => function ($query) {
                $query->latest('date_maintenance');
            },
        ]);

        $totalYear = VehicleMaintenance::where('vehicule_id', $vehicule->id)
            ->whereYear('date_maintenance', now()->year)
            ->sum('montant');

        return response()->json([
            'vehicule' => $vehicule,
            'total_year' => $totalYear,
            'total_maintenances' => $vehicule->maintenances->count(),
            'total_cost' => $vehicule->maintenances->sum('montant'),
        ]);
    }
}
