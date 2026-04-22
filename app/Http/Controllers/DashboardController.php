<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\Service;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfMonth();

        $baseQuery = Planning::query()
            ->with(['supplier', 'driver', 'guide', 'service'])
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()]);

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

        $activeSuppliers = (clone $baseQuery)
            ->whereNotNull('supplier_id')
            ->distinct('supplier_id')
            ->count('supplier_id');

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

        $assignedClients = PlanningClient::query()
            ->whereIn('planning_id', (clone $baseQuery)->pluck('id'))
            ->distinct('client_id')
            ->count('client_id');

        $recentPlannings = Planning::with(['supplier', 'driver', 'guide', 'service'])
            ->latest()
            ->take(8)
            ->get();

        $topSuppliers = Planning::query()
            ->select('supplier_id', DB::raw('COUNT(*) as total'))
            ->with('supplier:id,name')
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereNotNull('supplier_id')
            ->groupBy('supplier_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->supplier?->name ?? 'N/A',
                'total' => (int) $item->total,
            ])
            ->values();

        $topServices = Planning::query()
            ->select('service_id', DB::raw('COUNT(*) as total'))
            ->with('service:id,designation')
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
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
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
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
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
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

        $planningPerDayRaw = Planning::query()
            ->selectRaw('DATE(date_du) as label, COUNT(*) as total')
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $planningPerDay = $planningPerDayRaw->map(fn($row) => [
            'label' => Carbon::parse($row->label)->format('d/m'),
            'total' => (int) $row->total,
        ])->values();

        $budgetPerServiceRaw = Planning::query()
            ->select('service_id', DB::raw('SUM(budget) as total_budget'))
            ->with('service:id,designation')
            ->whereBetween('date_du', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('total_budget')
            ->take(6)
            ->get();

        $budgetPerService = $budgetPerServiceRaw->map(fn($row) => [
            'label' => $row->service?->designation ?? 'N/A',
            'total' => (float) $row->total_budget,
        ])->values();

        return Inertia::render('Dashboard', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'stats' => [
                'total_plannings' => $totalPlannings,
                'today_plannings' => $todayPlannings,
                'upcoming_plannings' => $upcomingPlannings,
                'total_budget' => round((float) $totalBudget, 2),
                'total_supplier_price' => round((float) $totalSupplierPrice, 2),
                'gross_margin' => round((float) $grossMargin, 2),
                'active_suppliers' => $activeSuppliers,
                'active_drivers' => $activeDrivers,
                'active_guides' => $activeGuides,
                'active_services' => $activeServices,
                'assigned_clients' => $assignedClients,
                'total_clients' => Client::count(),
                'total_suppliers' => Supplier::count(),
                'total_drivers' => Driver::count(),
                'total_guides' => Guide::count(),
                'total_services' => Service::count(),
            ],
            'topSuppliers' => $topSuppliers,
            'topServices' => $topServices,
            'topDrivers' => $topDrivers,
            'topGuides' => $topGuides,
            'planningPerDay' => $planningPerDay,
            'budgetPerService' => $budgetPerService,
            'recentPlannings' => $recentPlannings,
        ]);
    }
}
