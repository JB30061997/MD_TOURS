<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenance;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\MobileAdminAccess;

class MobileAdminVehicleMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $year = (int) $request->get('year', now()->year);
        $search = trim((string) $request->get('search', ''));

        $query = VehicleMaintenance::with('vehicule:id,matricule,marque,modele,type,status')
            ->whereYear('date_maintenance', $year)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('type_maintenance', 'like', "%{$search}%")
                        ->orWhere('garage', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('vehicule', function ($vehicle) use ($search) {
                            $vehicle->where('matricule', 'like', "%{$search}%")
                                ->orWhere('marque', 'like', "%{$search}%")
                                ->orWhere('modele', 'like', "%{$search}%");
                        });
                });
            });

        $maintenances = (clone $query)
            ->latest('date_maintenance')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'year' => $year,
            'stats' => [
                'total_amount' => (clone $query)->sum('montant'),
                'total_maintenances' => (clone $query)->count(),
                'vehicles_count' => Vehicule::count(),
            ],
            'top_vehicles' => VehicleMaintenance::query()
                ->select('vehicule_id', DB::raw('SUM(montant) as total_amount'), DB::raw('COUNT(*) as total_operations'))
                ->with('vehicule:id,matricule,marque,modele,type,status')
                ->whereYear('date_maintenance', $year)
                ->groupBy('vehicule_id')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get(),
            'maintenances' => $maintenances->items(),
            'pagination' => [
                'current_page' => $maintenances->currentPage(),
                'per_page' => $maintenances->perPage(),
                'total' => $maintenances->total(),
                'last_page' => $maintenances->lastPage(),
                'has_more' => $maintenances->hasMorePages(),
            ],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && app(MobileAdminAccess::class)->allowed($request->user()), 403);
    }
}
