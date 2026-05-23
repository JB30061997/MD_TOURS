<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Planning;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;

class MobileAdminSupplierVehiculeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $supplierVehicules = SupplierVehicule::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return response()->json($supplierVehicules);
    }

    public function show(SupplierVehicule $supplierVehicule)
    {
        $supplierVehicule->load('user:id,name,email');

        $linkedPlanningsCount = Planning::where('supplier_vehicule_id', $supplierVehicule->id)
            ->count();

        $linkedPlannings = Planning::with(['service', 'driver', 'guide', 'destination', 'vehicule'])
            ->where('supplier_vehicule_id', $supplierVehicule->id)
            ->latest('date_du')
            ->limit(20)
            ->get();

        return response()->json([
            'supplier_vehicule' => $supplierVehicule,
            'linked_plannings_count' => $linkedPlanningsCount,
            'linked_plannings' => $linkedPlannings,
        ]);
    }
}
