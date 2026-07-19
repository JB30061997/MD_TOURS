<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;
use App\Support\MobilePlanningSerializer;
use App\Support\MobileAdminAccess;

class MobileAdminPlanningController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(app(MobileAdminAccess::class)->can($request->user(), 'plannings.create'), 403);

        $data = $request->validate([
            'ref_dossier' => ['nullable', 'string', 'max:255'],
            'date_du' => ['required', 'date'],
            'date_au' => ['nullable', 'date', 'after_or_equal:date_du'],
            'heure' => ['nullable', 'date_format:H:i'],
            'point_depart' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'flight' => ['nullable', 'string', 'max:255'],
            'nbr_personnes' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $planning = Planning::create($data);

        return response()->json([
            'message' => 'Planning créé avec succès.',
            'planning' => MobilePlanningSerializer::enrich($planning),
        ], 201);
    }

    public function index(Request $request)
    {
        if ($this->isAdminRoute($request) && ! $this->isAdmin($request)) {
            abort(403, 'Admins only.');
        }

        $search = trim((string) $request->get('search', ''));

        $query = $this->basePlanningQuery();

        $this->applyUserScope($query, $request);

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

        $plannings->getCollection()->transform(
            fn (Planning $planning) => MobilePlanningSerializer::enrich($planning)
        );

        return response()->json($plannings);
    }

    public function show(Planning $planning)
    {
        if ($this->isAdminRoute(request()) && ! $this->isAdmin(request())) {
            abort(403, 'Admins only.');
        }

        $allowedQuery = Planning::query()->whereKey($planning->id);
        $this->applyUserScope($allowedQuery, request());

        abort_unless($allowedQuery->exists(), 404);

        $planning->load(MobilePlanningSerializer::relations());

        return response()->json([
            'planning' => MobilePlanningSerializer::enrich($planning),
        ]);
    }

    private function basePlanningQuery()
    {
        return Planning::query()->with(MobilePlanningSerializer::relations());
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user && app(MobileAdminAccess::class)->allowed($user);
    }

    private function isAdminRoute(Request $request): bool
    {
        return str_contains($request->path(), 'mobile/admin/');
    }

    private function applyUserScope($query, Request $request): void
    {
        $user = $request->user();

        if (! $user || $this->isAdmin($request)) {
            return;
        }

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        $driver = in_array('driver', $roles, true)
            ? Driver::where('user_id', $user->id)->first()
            : null;

        $guide = in_array('guide', $roles, true)
            ? Guide::where('user_id', $user->id)->first()
            : null;

        $supplierClient = in_array('supplier_client', $roles, true)
            ? SupplierClient::where('user_id', $user->id)->first()
            : null;

        $supplierVehicule = in_array('supplier_vehicule', $roles, true)
            ? SupplierVehicule::where('user_id', $user->id)->first()
            : null;

        if (! $driver && ! $guide && ! $supplierClient && ! $supplierVehicule) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($scope) use ($driver, $guide, $supplierClient, $supplierVehicule) {
            if ($driver) {
                $scope->orWhere('driver_id', $driver->id);
            }

            if ($guide) {
                $scope->orWhere('guide_id', $guide->id);
            }

            if ($supplierClient) {
                $scope->orWhereHas('planningClients.client', function ($clientQuery) use ($supplierClient) {
                    $clientQuery->where('supplier_client_id', $supplierClient->id);
                });
            }

            if ($supplierVehicule) {
                $scope->orWhere('supplier_vehicule_id', $supplierVehicule->id);
            }
        });
    }
}
