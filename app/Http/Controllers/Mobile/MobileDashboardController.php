<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Planning;
use App\Models\Driver;
use App\Models\Guide;
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

                if ($profile) {
                    $query->where('driver_id', $profile->id);
                }
            }

            if (in_array('guide', $roles)) {
                $profile = Guide::where('user_id', $user->id)->first();
                $profileType = 'guide';

                if ($profile) {
                    $query->where('guide_id', $profile->id);
                }
            }

            if (in_array('supplier_client', $roles)) {
                $profile = SupplierClient::where('user_id', $user->id)->first();
                $profileType = 'supplier_client';

                if ($profile) {
                    $query->where('supplier_client_id', $profile->id);
                }
            }

            if (in_array('supplier_vehicule', $roles)) {
                $profile = SupplierVehicule::where('user_id', $user->id)->first();
                $profileType = 'supplier_vehicule';

                if ($profile) {
                    $query->where('supplier_vehicule_id', $profile->id);
                }
            }
        }

        $plannings = $query->limit(10)->get();

        return response()->json([
            'role' => $isAdmin ? 'admin' : ($roles[0] ?? 'user'),
            'profile_type' => $profileType,
            'profile' => $profile,

            'stats' => [
                'total_plannings' => (clone $query)->count(),
                'today_plannings' => (clone $query)->whereDate('date_du', today())->count(),
                'upcoming_plannings' => (clone $query)->whereDate('date_du', '>=', today())->count(),
            ],

            'latest_plannings' => $plannings,
        ]);
    }
}
