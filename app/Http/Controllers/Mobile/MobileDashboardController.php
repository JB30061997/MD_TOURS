<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        $isAdmin = in_array('admin', $roles) || in_array('administrateur', $roles);

        $query = Planning::query()
            ->with([
                'service',
                'driver',
                'guide',
                'destination',
                'supplierClient',
                'supplierVehicule',
                'vehicule',
            ])
            ->latest('date_du');

        $profile = null;
        $profileType = null;

        if (! $isAdmin) {
            if (in_array('driver', $roles)) {
                $profile = Driver::where('user_id', $user->id)->first();
                $profileType = 'driver';

                $profile
                    ? $query->where('driver_id', $profile->id)
                    : $query->whereRaw('1 = 0');
            } elseif (in_array('guide', $roles)) {
                $profile = Guide::where('user_id', $user->id)->first();
                $profileType = 'guide';

                $profile
                    ? $query->where('guide_id', $profile->id)
                    : $query->whereRaw('1 = 0');
            } elseif (in_array('supplier_client', $roles)) {
                $profile = SupplierClient::where('user_id', $user->id)->first();
                $profileType = 'supplier_client';

                $profile
                    ? $query->where('supplier_client_id', $profile->id)
                    : $query->whereRaw('1 = 0');
            } elseif (in_array('supplier_vehicule', $roles)) {
                $profile = SupplierVehicule::where('user_id', $user->id)->first();
                $profileType = 'supplier_vehicule';

                $profile
                    ? $query->where('supplier_vehicule_id', $profile->id)
                    : $query->whereRaw('1 = 0');
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_du', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_du', '<=', $request->date_to);
        }

        $baseQuery = clone $query;

        $plannings = (clone $query)
            ->limit(10)
            ->get();

        return response()->json([
            'role' => $isAdmin ? 'admin' : ($roles[0] ?? 'user'),
            'roles' => $roles,
            'profile_type' => $profileType,
            'profile' => $profile,

            'stats' => [
                'total_plannings' => (clone $baseQuery)->count(),
                'today_plannings' => (clone $baseQuery)->whereDate('date_du', today())->count(),
                'upcoming_plannings' => (clone $baseQuery)->whereDate('date_du', '>=', today())->count(),
            ],

            'latest_plannings' => $plannings,
        ]);
    }
}
