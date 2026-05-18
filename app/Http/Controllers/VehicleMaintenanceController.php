<?php

namespace App\Http\Controllers;

use App\Models\Vehicule;
use App\Models\VehicleMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VehicleMaintenanceController extends Controller
{
    public function index($vehiculeId)
    {
        $vehicule = Vehicule::with([
            'maintenances' => function ($query) {
                $query->latest('date_maintenance');
            }
        ])->findOrFail($vehiculeId);

        $totalYear = VehicleMaintenance::where('vehicule_id', $vehiculeId)
            ->whereYear('date_maintenance', now()->year)
            ->sum('montant');

        return Inertia::render('VehicleMaintenances/Index', [
            'vehicule' => $vehicule,
            'totalYear' => $totalYear,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'type_maintenance' => 'required|string|max:255',
            'date_maintenance' => 'required|date',
            'kilometrage' => 'nullable|integer|min:0',
            'montant' => 'required|numeric|min:0',
            'garage' => 'nullable|string|max:255',
            'prochaine_date' => 'nullable|date',
            'prochain_kilometrage' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'effectue';

        VehicleMaintenance::create($validated);

        return redirect()
            ->route('vehicle-maintenances.index', $validated['vehicule_id'])
            ->with('success', 'Maintenance ajoutée avec succès.');
    }

    public function update(Request $request, $id)
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        $validated = $request->validate([
            'type_maintenance' => 'required|string|max:255',
            'date_maintenance' => 'required|date',
            'kilometrage' => 'nullable|integer|min:0',
            'montant' => 'required|numeric|min:0',
            'garage' => 'nullable|string|max:255',
            'prochaine_date' => 'nullable|date',
            'prochain_kilometrage' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'effectue';

        $maintenance->update($validated);

        return redirect()
            ->route('vehicle-maintenances.index', $maintenance->vehicule_id)
            ->with('success', 'Maintenance modifiée avec succès.');
    }

    public function destroy($id)
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        $vehiculeId = $maintenance->vehicule_id;

        $maintenance->delete();

        return redirect()
            ->route('vehicle-maintenances.index', $vehiculeId)
            ->with('success', 'Maintenance supprimée avec succès.');
    }


    public function report(Request $request)
    {
        $year = $request->get('year', now()->year);

        $totalYear = VehicleMaintenance::whereYear('date_maintenance', $year)
            ->sum('montant');

        $totalMaintenances = VehicleMaintenance::whereYear('date_maintenance', $year)
            ->count();

        $vehiclesCount = Vehicule::count();

        $topVehicles = VehicleMaintenance::query()
            ->select(
                'vehicule_id',
                DB::raw('SUM(montant) as total_amount'),
                DB::raw('COUNT(*) as total_operations')
            )
            ->with('vehicule:id,matricule,marque,modele,type,status')
            ->whereYear('date_maintenance', $year)
            ->groupBy('vehicule_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $byType = VehicleMaintenance::query()
            ->select(
                'type_maintenance',
                DB::raw('SUM(montant) as total_amount'),
                DB::raw('COUNT(*) as total_operations')
            )
            ->whereYear('date_maintenance', $year)
            ->groupBy('type_maintenance')
            ->orderByDesc('total_amount')
            ->get();

        $monthly = VehicleMaintenance::query()
            ->select(
                DB::raw('MONTH(date_maintenance) as month'),
                DB::raw('SUM(montant) as total_amount'),
                DB::raw('COUNT(*) as total_operations')
            )
            ->whereYear('date_maintenance', $year)
            ->groupBy(DB::raw('MONTH(date_maintenance)'))
            ->orderBy('month')
            ->get();

        $latestMaintenances = VehicleMaintenance::with('vehicule:id,matricule,marque,modele,type,status')
            ->latest('date_maintenance')
            ->limit(12)
            ->get();

        return Inertia::render('VehicleMaintenances/Report', [
            'year' => (int) $year,
            'totalYear' => $totalYear,
            'totalMaintenances' => $totalMaintenances,
            'vehiclesCount' => $vehiclesCount,
            'topVehicles' => $topVehicles,
            'byType' => $byType,
            'monthly' => $monthly,
            'latestMaintenances' => $latestMaintenances,
        ]);
    }
}
