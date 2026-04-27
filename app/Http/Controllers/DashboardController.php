<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\Vehicule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /*
            ila user ma filterach b date:
            kanjibou automatiquement akher mois fih data f planning.
            matalan ila data wa9fa f mois 3, dashboard ghadi yban b mois 3.
        */
        $latestPlanningDate = Planning::query()
            ->whereNotNull('date_du')
            ->max('date_du');

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from')
                ? Carbon::parse($request->date_from)->startOfDay()
                : Carbon::parse($request->date_to)->startOfMonth();

            $dateTo = $request->filled('date_to')
                ? Carbon::parse($request->date_to)->endOfDay()
                : Carbon::parse($request->date_from)->endOfMonth();
        } elseif ($latestPlanningDate) {
            $dateFrom = Carbon::parse($latestPlanningDate)->startOfMonth();
            $dateTo = Carbon::parse($latestPlanningDate)->endOfMonth();
        } else {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfMonth();
        }

        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();

        $baseQuery = Planning::query()
            ->with([
                'supplierVehicule',
                'driver',
                'guide',
                'service',
                'destination',
                'vehicule',
                'planningClients.client',
            ])
            ->whereBetween('date_du', [$dateFromString, $dateToString]);

        $basePlanningIds = (clone $baseQuery)->pluck('id');

        $totalPlannings = (clone $baseQuery)->count();

        $todayPlannings = Planning::query()
            ->whereDate('date_du', now()->toDateString())
            ->count();

        $upcomingPlannings = Planning::query()
            ->whereDate('date_du', '>=', now()->toDateString())
            ->count();

        $totalBudget = (clone $baseQuery)->sum('budget');
        $totalSupplierPrice = (clone $baseQuery)->sum('supplier_price');
        $grossMargin = (float) $totalBudget - (float) $totalSupplierPrice;

        $activeSupplierVehicules = (clone $baseQuery)
            ->whereNotNull('supplier_vehicule_id')
            ->distinct('supplier_vehicule_id')
            ->count('supplier_vehicule_id');

        $activeVehicules = (clone $baseQuery)
            ->whereNotNull('vehicule_id')
            ->distinct('vehicule_id')
            ->count('vehicule_id');

        $activeDrivers = (clone $baseQuery)
            ->whereNotNull('driver_id')
            ->distinct('driver_id')
            ->count('driver_id');

        $activeGuides = (clone $baseQuery)
            ->whereNotNull('guide_id')
            ->distinct('guide_id')
            ->count('guide_id');

        $activeServices = (clone $baseQuery)
            ->whereNotNull('service_id')
            ->distinct('service_id')
            ->count('service_id');

        $activeDestinations = (clone $baseQuery)
            ->whereNotNull('destination_id')
            ->distinct('destination_id')
            ->count('destination_id');

        $assignedClients = PlanningClient::query()
            ->whereIn('planning_id', $basePlanningIds)
            ->distinct('client_id')
            ->count('client_id');

        $recentPlannings = Planning::query()
            ->with([
                'supplierVehicule',
                'driver',
                'guide',
                'service',
                'destination',
                'vehicule',
                'planningClients.client',
            ])
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $topSupplierVehicules = Planning::query()
            ->select('supplier_vehicule_id', DB::raw('COUNT(*) as total'))
            ->with('supplierVehicule:id,name')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('supplier_vehicule_id')
            ->groupBy('supplier_vehicule_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->supplierVehicule?->name ?? 'N/A',
                'total' => (int) $item->total,
            ])
            ->values();

        $topServices = Planning::query()
            ->select('service_id', DB::raw('COUNT(*) as total'))
            ->with('service:id,designation')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->service?->designation ?? 'N/A',
                'total' => (int) $item->total,
            ])
            ->values();

        $topDrivers = Planning::query()
            ->select('driver_id', DB::raw('COUNT(*) as total'))
            ->with('driver:id,name')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('driver_id')
            ->groupBy('driver_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->driver?->name ?? 'N/A',
                'total' => (int) $item->total,
            ])
            ->values();

        $topGuides = Planning::query()
            ->select('guide_id', DB::raw('COUNT(*) as total'))
            ->with('guide:id,name')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('guide_id')
            ->groupBy('guide_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->guide?->name ?? 'N/A',
                'total' => (int) $item->total,
            ])
            ->values();

        $topDestinations = Planning::query()
            ->select('destination_id', DB::raw('COUNT(*) as total'))
            ->with('destination:id,name,city')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('destination_id')
            ->groupBy('destination_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => trim(($item->destination?->name ?? 'N/A') . ($item->destination?->city ? ' - ' . $item->destination->city : '')),
                'total' => (int) $item->total,
            ])
            ->values();

        $planningPerDayRaw = Planning::query()
            ->selectRaw('DATE(date_du) as label, COUNT(*) as total')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $planningPerDay = $planningPerDayRaw
            ->map(fn($row) => [
                'label' => Carbon::parse($row->label)->format('d/m'),
                'date' => Carbon::parse($row->label)->format('Y-m-d'),
                'total' => (int) $row->total,
            ])
            ->values();

        $budgetPerServiceRaw = Planning::query()
            ->select('service_id', DB::raw('SUM(budget) as total_budget'))
            ->with('service:id,designation')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('total_budget')
            ->take(6)
            ->get();

        $budgetPerService = $budgetPerServiceRaw
            ->map(fn($row) => [
                'label' => $row->service?->designation ?? 'N/A',
                'total' => round((float) $row->total_budget, 2),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'filters' => [
                'date_from' => $dateFromString,
                'date_to' => $dateToString,
            ],
            'periodInfo' => [
                'latest_planning_date' => $latestPlanningDate
                    ? Carbon::parse($latestPlanningDate)->toDateString()
                    : null,
                'period_label' => $dateFrom->translatedFormat('F Y'),
                'is_auto_period' => !$request->filled('date_from') && !$request->filled('date_to'),
            ],
            'stats' => [
                'total_plannings' => $totalPlannings,
                'today_plannings' => $todayPlannings,
                'upcoming_plannings' => $upcomingPlannings,

                'total_budget' => round((float) $totalBudget, 2),
                'total_supplier_price' => round((float) $totalSupplierPrice, 2),
                'gross_margin' => round((float) $grossMargin, 2),

                'active_supplier_vehicules' => $activeSupplierVehicules,
                'active_vehicules' => $activeVehicules,
                'active_drivers' => $activeDrivers,
                'active_guides' => $activeGuides,
                'active_services' => $activeServices,
                'active_destinations' => $activeDestinations,
                'assigned_clients' => $assignedClients,

                'total_clients' => Client::count(),
                'total_supplier_clients' => SupplierClient::count(),
                'total_supplier_vehicules' => SupplierVehicule::count(),
                'total_vehicules' => Vehicule::count(),
                'total_drivers' => Driver::count(),
                'total_guides' => Guide::count(),
                'total_services' => Service::count(),
                'total_destinations' => Destination::count(),
            ],
            'topSupplierVehicules' => $topSupplierVehicules,
            'topServices' => $topServices,
            'topDrivers' => $topDrivers,
            'topGuides' => $topGuides,
            'topDestinations' => $topDestinations,
            'planningPerDay' => $planningPerDay,
            'budgetPerService' => $budgetPerService,
            'recentPlannings' => $recentPlannings,
        ]);
    }
}
