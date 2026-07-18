<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\MobilePlanningSerializer;

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
                'planningClients.client.supplierClient',
                'supplierVehicule',
                'vehicule',
            ]);

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
                    ? $query->whereHas('planningClients.client', fn ($clientQuery) => $clientQuery->where('supplier_client_id', $profile->id))
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

        $perPage = (int) $request->get('per_page', 20);

        if ($perPage < 1) {
            $perPage = 20;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        $plannings = (clone $query)
            ->orderBy('date_du', 'asc')
            ->orderBy('heure', 'asc')
            ->paginate($perPage);

        $plannings->getCollection()->transform(
            fn (Planning $planning) => MobilePlanningSerializer::enrich($planning)
        );

        return response()->json([
            'role' => $isAdmin ? 'admin' : ($roles[0] ?? 'user'),
            'roles' => $roles,
            'profile_type' => $profileType,
            'profile' => $profile,

            'stats' => [
                'total_plannings' => (clone $baseQuery)->count(),
                'today_plannings' => (clone $baseQuery)->whereDate('date_du', today())->count(),
                'tomorrow_plannings' => (clone $baseQuery)->whereDate('date_du', today()->addDay())->count(),
                'upcoming_plannings' => (clone $baseQuery)->whereDate('date_du', '>=', today())->count(),
            ],

            'financial' => [
                'total_budget' => (clone $baseQuery)->sum('budget'),
                'supplier_price' => (clone $baseQuery)->sum('supplier_price'),
                'profit' => (clone $baseQuery)->sum('budget') - (clone $baseQuery)->sum('supplier_price'),
            ],

            'alerts' => [
                [
                    'id' => 'missing_driver',
                    'title' => 'Plannings without driver',
                    'value' => (clone $baseQuery)->whereNull('driver_id')->count(),
                ],
                [
                    'id' => 'missing_supplier_vehicle',
                    'title' => 'Plannings without supplier vehicle',
                    'value' => (clone $baseQuery)->whereNull('supplier_vehicule_id')->count(),
                ],
                [
                    'id' => 'missing_destination',
                    'title' => 'Plannings without destination',
                    'value' => (clone $baseQuery)->whereNull('destination_id')->count(),
                ],
            ],

            'chart' => (clone $baseQuery)
                ->selectRaw('DATE(date_du) as label, COUNT(*) as count')
                ->whereNotNull('date_du')
                ->groupBy(DB::raw('DATE(date_du)'))
                ->orderBy('label', 'desc')
                ->limit(7)
                ->get()
                ->reverse()
                ->values(),

            'latest_plannings' => $plannings->items(),

            'pagination' => [
                'current_page' => $plannings->currentPage(),
                'per_page' => $plannings->perPage(),
                'total' => $plannings->total(),
                'last_page' => $plannings->lastPage(),
                'has_more' => $plannings->hasMorePages(),
                'next_page_url' => $plannings->nextPageUrl(),
                'prev_page_url' => $plannings->previousPageUrl(),
            ],
        ]);
    }
}
