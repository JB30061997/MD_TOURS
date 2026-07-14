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
use App\Models\SupplierVehiculeInvoice;
use App\Models\SupplierVehiculeInvoicePlanning;
use App\Models\SupplierVehiculeInvoicePayment;
use App\Models\Vehicule;
use App\Http\Requests\UpdatePlanningServiceRequest;
use App\Services\PlanningServiceMatcher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()?->can('dashboard.view')) {
            return Inertia::render('DashboardRestricted');
        }

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
        } else {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfMonth();
        }

        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();
        $supplierVehiculeId = $request->filled('supplier_vehicule_id')
            ? (int) $request->supplier_vehicule_id
            : null;

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

        if ($supplierVehiculeId) {
            $baseQuery->where('supplier_vehicule_id', $supplierVehiculeId);
        }

        $basePlanningIds = (clone $baseQuery)->pluck('id');

        $totalPlannings = (clone $baseQuery)->count();

        $todayPlannings = Planning::query()
            ->whereDate('date_du', now()->toDateString())
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->count();

        $upcomingPlannings = Planning::query()
            ->whereDate('date_du', '>=', now()->toDateString())
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->count();

        $totalBudget = (clone $baseQuery)->sum('budget');
        $totalSupplierPrice = (clone $baseQuery)->sum('supplier_price');
        $grossMargin = (float) $totalBudget - (float) $totalSupplierPrice;
        $supplierPayments = $this->supplierPaymentTotal(
            $dateFromString,
            $dateToString,
            $supplierVehiculeId
        );
        $previousDateFrom = $dateFrom->copy()->subMonthNoOverflow()->startOfMonth();
        $previousDateTo = $dateFrom->copy()->subMonthNoOverflow()->endOfMonth();
        $previousFinancials = $this->financialTotals(
            $previousDateFrom->toDateString(),
            $previousDateTo->toDateString(),
            $supplierVehiculeId
        );

        $monthlyFinancialSummary = [
            [
                'key' => 'budget',
                'label' => 'Budget total',
                'value' => round((float) $totalBudget, 2),
                'previous_value' => $previousFinancials['total_budget'],
                'change_percent' => $this->percentageChange((float) $totalBudget, $previousFinancials['total_budget']),
                'trend' => $this->trendDirection((float) $totalBudget, $previousFinancials['total_budget']),
            ],
            [
                'key' => 'supplier_cost',
                'label' => 'Coût fournisseurs',
                'value' => round((float) $totalSupplierPrice, 2),
                'previous_value' => $previousFinancials['total_supplier_price'],
                'change_percent' => $this->percentageChange((float) $totalSupplierPrice, $previousFinancials['total_supplier_price']),
                'trend' => $this->trendDirection((float) $totalSupplierPrice, $previousFinancials['total_supplier_price']),
            ],
            [
                'key' => 'supplier_payments',
                'label' => 'Paiements fournisseurs',
                'value' => $supplierPayments,
                'previous_value' => $previousFinancials['supplier_payments'],
                'change_percent' => $this->percentageChange($supplierPayments, $previousFinancials['supplier_payments']),
                'trend' => $this->trendDirection($supplierPayments, $previousFinancials['supplier_payments']),
            ],
            [
                'key' => 'gross_margin',
                'label' => 'Marge brute',
                'value' => round((float) $grossMargin, 2),
                'previous_value' => $previousFinancials['gross_margin'],
                'change_percent' => $this->percentageChange((float) $grossMargin, $previousFinancials['gross_margin']),
                'trend' => $this->trendDirection((float) $grossMargin, $previousFinancials['gross_margin']),
            ],
        ];

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
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $topSupplierVehicules = Planning::query()
            ->select('supplier_vehicule_id', DB::raw('COUNT(*) as total'))
            ->with('supplierVehicule:id,name')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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
            ->selectRaw('DATE(date_du) as date_label, COUNT(*) as total')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('date_label')
            ->orderBy('date_label')
            ->get();

        $planningPerDay = $planningPerDayRaw
            ->map(fn($row) => [
                'label' => Carbon::parse($row->date_label)->format('d/m'),
                'date' => Carbon::parse($row->date_label)->format('Y-m-d'),
                'total' => (int) $row->total,
            ])
            ->values();

        $budgetPerServiceRaw = Planning::query()
            ->select('service_id', DB::raw('SUM(budget) as total_budget'))
            ->with('service:id,designation')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
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

        /*
            Rapport wa3er:
            Analytics par jour:
            - total plannings
            - budget
            - prix fournisseur
            - marge
            - clients
        */
        $financialByDay = Planning::query()
            ->selectRaw('
                DATE(date_du) as date_label,
                COUNT(*) as total_plannings,
                COALESCE(SUM(budget), 0) as total_budget,
                COALESCE(SUM(supplier_price), 0) as total_supplier_price
            ')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('date_label')
            ->get()
            ->keyBy('date_label');

        $clientsByDay = PlanningClient::query()
            ->join('plannings', 'planning_clients.planning_id', '=', 'plannings.id')
            ->selectRaw('
                DATE(plannings.date_du) as date_label,
                COUNT(DISTINCT planning_clients.client_id) as total_clients
            ')
            ->whereBetween('plannings.date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('plannings.supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('date_label')
            ->get()
            ->keyBy('date_label');

        $planningAnalytics = collect(CarbonPeriod::create($dateFromString, $dateToString))
            ->map(function ($date) use ($financialByDay, $clientsByDay) {
                $date = Carbon::parse($date);

                $key = $date->format('Y-m-d');

                $financial = $financialByDay->get($key);
                $clients = $clientsByDay->get($key);

                $budget = round((float) ($financial?->total_budget ?? 0), 2);
                $supplierPrice = round((float) ($financial?->total_supplier_price ?? 0), 2);

                return [
                    'date' => $key,
                    'label' => $date->format('d/m'),
                    'total_plannings' => (int) ($financial?->total_plannings ?? 0),
                    'total_budget' => $budget,
                    'total_supplier_price' => $supplierPrice,
                    'gross_margin' => round($budget - $supplierPrice, 2),
                    'total_clients' => (int) ($clients?->total_clients ?? 0),
                ];
            })
            ->values();

        $planningAnalyticsHierarchy = $this->planningAnalyticsHierarchy(
            (int) $dateFrom->year,
            $supplierVehiculeId
        );

        $supplierVehiculePerformance = Planning::query()
            ->selectRaw('
                supplier_vehicule_id,
                COUNT(*) as total_trips,
                COALESCE(SUM(budget), 0) as total_budget,
                COALESCE(SUM(supplier_price), 0) as total_supplier_price,
                COALESCE(SUM(budget), 0) - COALESCE(SUM(supplier_price), 0) as gross_margin
            ')
            ->with('supplierVehicule:id,name')
            ->whereBetween('date_du', [$dateFromString, $dateToString])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('supplier_vehicule_id')
            ->orderByDesc('total_trips')
            ->take(10)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->supplier_vehicule_id ?: 'none',
                'name' => $item->supplierVehicule?->name ?? 'Sans fournisseur véhicule',
                'total_trips' => (int) $item->total_trips,
                'total_budget' => round((float) $item->total_budget, 2),
                'total_supplier_price' => round((float) $item->total_supplier_price, 2),
                'gross_margin' => round((float) $item->gross_margin, 2),
            ])
            ->values();

        $supplierServiceDrilldown = $this->supplierServiceDrilldown(
            $dateFromString,
            $dateToString,
            $supplierVehiculeId
        );

        $supplierVehicleInvoiceOptions = SupplierVehiculeInvoice::query()
            ->with('payments')
            ->select('id', 'supplier_vehicule_id', 'invoice_number', 'period_start', 'period_end', 'invoice_date', 'total_amount')
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->whereNotNull('supplier_vehicule_id')
            ->where(function ($query) use ($dateFromString, $dateToString) {
                $query->where(function ($periodQuery) use ($dateFromString, $dateToString) {
                    $periodQuery->whereDate('period_start', '<=', $dateToString)
                        ->whereDate('period_end', '>=', $dateFromString);
                })->orWhereBetween('invoice_date', [$dateFromString, $dateToString]);
            })
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->get()
            ->groupBy('supplier_vehicule_id')
            ->map(fn ($invoices) => $invoices->map(function ($invoice) {
                $paidAmount = round((float) $invoice->payments->sum('amount'), 2);
                $totalAmount = round((float) $invoice->total_amount, 2);

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number ?: ('#' . $invoice->id),
                    'period' => trim(($invoice->period_start?->format('d/m/Y') ?: '-') . ' → ' . ($invoice->period_end?->format('d/m/Y') ?: '-')),
                    'invoice_date' => $invoice->invoice_date?->format('d/m/Y') ?: '-',
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => max(round($totalAmount - $paidAmount, 2), 0),
                ];
            })->values())
            ->all();

        $vehicleEfficiency = $this->vehicleEfficiency(
            $dateFromString,
            $dateToString,
            $supplierVehiculeId
        );

        return Inertia::render('Dashboard', [
            'filters' => [
                'date_from' => $dateFromString,
                'date_to' => $dateToString,
                'supplier_vehicule_id' => $supplierVehiculeId ?: '',
            ],
            'supplierVehicules' => SupplierVehicule::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'supplierVehicleInvoiceOptions' => $supplierVehicleInvoiceOptions,
            'canLinkSupplierInvoice' => $request->user()?->can('supplier-vehicule-invoices.edit') === true,
            'services' => Service::query()->orderBy('designation')->get(['id', 'designation']),
            'canEditPlanningService' => $request->user()?->can('plannings.edit') === true,
            'canAssignPlanningSupplier' => $request->user()?->can('plannings.edit') === true,
            'canManageMissingSuppliers' => $request->user()?->isSuperAdmin() === true,

            'periodInfo' => [
                'latest_planning_date' => $latestPlanningDate
                    ? Carbon::parse($latestPlanningDate)->toDateString()
                    : null,
                'period_label' => $dateFrom->translatedFormat('F Y'),
                'is_auto_period' => !$request->filled('date_from') && !$request->filled('date_to'),
                'previous_period_label' => $previousDateFrom->translatedFormat('F Y'),
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
            'planningAnalytics' => $planningAnalytics,
            'planningAnalyticsHierarchy' => $planningAnalyticsHierarchy,
            'supplierVehiculePerformance' => $supplierVehiculePerformance,
            'supplierServiceDrilldown' => $supplierServiceDrilldown,
            'monthlyFinancialSummary' => $monthlyFinancialSummary,
            'vehicleEfficiency' => $vehicleEfficiency,

            'recentPlannings' => $recentPlannings,
        ]);
    }

    public function updatePlanningService(
        UpdatePlanningServiceRequest $request,
        Planning $planning
    ) {
        $data = $request->validated();

        if ($planning->service_id && !$data['replace_confirmed']) {
            return back()->withErrors([
                'service_id' => 'La confirmation est obligatoire pour remplacer le service actuel.',
            ]);
        }

        $planning->update(['service_id' => $data['service_id']]);

        return back()->with('success', 'Service du planning mis à jour avec succès.');
    }

    public function linkPlanningToSupplierInvoice(Request $request, Planning $planning)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:supplier_vehicule_invoices,id'],
        ]);

        if (!$planning->supplier_vehicule_id) {
            return back()->with('error', "Ce service n'a pas de fournisseur véhicule.");
        }

        $invoice = SupplierVehiculeInvoice::query()
            ->whereKey($data['invoice_id'])
            ->where('supplier_vehicule_id', $planning->supplier_vehicule_id)
            ->first();

        if (!$invoice) {
            return back()->with('error', 'Cette facture ne correspond pas au fournisseur véhicule du service.');
        }

        DB::transaction(function () use ($planning, $invoice) {
            SupplierVehiculeInvoicePlanning::query()
                ->where('planning_id', $planning->id)
                ->update(['is_selected' => false]);

            SupplierVehiculeInvoicePlanning::query()->updateOrCreate(
                [
                    'supplier_vehicule_invoice_id' => $invoice->id,
                    'planning_id' => $planning->id,
                ],
                [
                    'is_selected' => true,
                    'notes' => 'Lié depuis le Dashboard',
                ]
            );
        });

        return back()->with('success', 'Service lié à la facture fournisseur avec succès.');
    }

    private function supplierServiceDrilldown(string $dateFrom, string $dateTo, ?int $supplierVehiculeId = null): array
    {
        $availableServices = Service::query()->orderBy('designation')->get();
        $matcher = app(PlanningServiceMatcher::class);
        $rows = Planning::query()
            ->selectRaw('
                supplier_vehicule_id,
                service_id,
                DATE(date_du) as date_label,
                COUNT(*) as total_trips,
                COALESCE(SUM(budget), 0) as total_budget,
                COALESCE(SUM(supplier_price), 0) as total_supplier_price,
                COALESCE(SUM(budget), 0) - COALESCE(SUM(supplier_price), 0) as gross_margin
            ')
            ->with([
                'supplierVehicule:id,name',
                'service:id,designation',
            ])
            ->whereBetween('date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('supplier_vehicule_id', 'service_id', 'date_label')
            ->orderBy('date_label')
            ->get();

        $planningDetails = Planning::query()
            ->with([
                'service:id,designation',
                'supplierVehicule:id,name',
                'supplierClient:id,name',
                'driver:id,name',
                'guide:id,name',
                'destination:id,name,city',
                'vehicule:id,matricule,marque,modele',
                'planningClients.client:id,full_name',
            ])
            ->whereBetween('date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->orderBy('date_du')
            ->orderBy('heure')
            ->get();

        $planningInvoiceLinks = SupplierVehiculeInvoicePlanning::query()
            ->with(['invoice.payments'])
            ->whereIn('planning_id', $planningDetails->pluck('id'))
            ->get()
            ->groupBy('planning_id');

        $planningDetailsByBucket = $planningDetails
            ->map(function ($planning) use ($planningInvoiceLinks, $availableServices, $matcher) {
                return $this->dashboardPlanningDetail(
                    $planning,
                    $planningInvoiceLinks->get($planning->id, collect()),
                    $availableServices,
                    $matcher
                );
            })
            ->groupBy('bucket_key');

        return $rows
            ->groupBy(fn ($row) => $row->supplier_vehicule_id ?: 'none')
            ->map(function ($supplierRows, $supplierKey) use ($planningDetailsByBucket) {
                $first = $supplierRows->first();
                $services = $supplierRows
                    ->groupBy(fn ($row) => $row->service_id ?: 'none')
                    ->map(function ($serviceRows, $serviceKey) use ($planningDetailsByBucket) {
                        $serviceFirst = $serviceRows->first();
                        $days = $serviceRows
                            ->map(function ($row) use ($planningDetailsByBucket) {
                                $budget = round((float) $row->total_budget, 2);
                                $supplierPrice = round((float) $row->total_supplier_price, 2);
                                $date = Carbon::parse($row->date_label)->toDateString();
                                $bucketKey = implode('|', [
                                    $row->supplier_vehicule_id ?: 'none',
                                    $row->service_id ?: 'none',
                                    $date,
                                ]);
                                $plannings = $planningDetailsByBucket
                                    ->get($bucketKey, collect())
                                    ->values()
                                    ->map(fn ($detail) => collect($detail)->except('bucket_key')->all())
                                    ->all();

                                return [
                                    'date' => $date,
                                    'label' => Carbon::parse($row->date_label)->format('d/m'),
                                    'day_label' => Carbon::parse($row->date_label)->translatedFormat('D d/m'),
                                    'total_trips' => (int) $row->total_trips,
                                    'total_budget' => $budget,
                                    'total_supplier_price' => $supplierPrice,
                                    'gross_margin' => round($budget - $supplierPrice, 2),
                                    'plannings' => $plannings,
                                ];
                            })
                            ->values();

                        return [
                            'id' => (string) $serviceKey,
                            'name' => $serviceFirst->service?->designation ?? 'Sans service',
                            'total_trips' => (int) $days->sum('total_trips'),
                            'total_budget' => round((float) $days->sum('total_budget'), 2),
                            'total_supplier_price' => round((float) $days->sum('total_supplier_price'), 2),
                            'gross_margin' => round((float) $days->sum('gross_margin'), 2),
                            'days' => $days,
                        ];
                    })
                    ->sortByDesc('total_trips')
                    ->values();

                return [
                    'id' => (string) $supplierKey,
                    'name' => $first->supplierVehicule?->name ?? 'Sans fournisseur véhicule',
                    'total_trips' => (int) $services->sum('total_trips'),
                    'total_budget' => round((float) $services->sum('total_budget'), 2),
                    'total_supplier_price' => round((float) $services->sum('total_supplier_price'), 2),
                    'gross_margin' => round((float) $services->sum('gross_margin'), 2),
                    'services' => $services,
                ];
            })
            ->sortByDesc('total_trips')
            ->values()
            ->all();
    }

    private function dashboardPlanningDetail(
        Planning $planning,
        $invoiceLinks,
        $availableServices,
        PlanningServiceMatcher $matcher
    ): array
    {
        $invoiceLink = $invoiceLinks->firstWhere('is_selected', true) ?? $invoiceLinks->first();
        $invoice = $invoiceLink?->invoice;
        $invoiceSnapshot = null;

        if ($invoice) {
            $invoiceTotal = round((float) $invoice->total_amount, 2);
            $paidAmount = round((float) $invoice->payments->sum('amount'), 2);
            $remainingAmount = max(round($invoiceTotal - $paidAmount, 2), 0);
            $paymentStatus = $paidAmount <= 0
                ? 'unpaid'
                : ($remainingAmount <= 0.01 ? 'paid' : 'partial');

            $invoiceSnapshot = [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date?->format('d/m/Y'),
                'total_amount' => $invoiceTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payments_count' => $invoice->payments->count(),
                'payment_status' => $paymentStatus,
                'payment_label' => match ($paymentStatus) {
                    'paid' => 'Payée',
                    'partial' => 'Payée partiellement',
                    default => 'Non payée',
                },
            ];
        }

        $budget = round((float) $planning->budget, 2);
        $supplierPrice = round((float) $planning->supplier_price, 2);
        $recommendation = $matcher->recommend($planning, $availableServices);

        return [
            'bucket_key' => implode('|', [
                $planning->supplier_vehicule_id ?: 'none',
                $planning->service_id ?: 'none',
                $planning->date_du?->toDateString(),
            ]),
            'id' => $planning->id,
            'supplier_vehicle_id' => $planning->supplier_vehicule_id,
            'ref_dossier' => $planning->ref_dossier ?: 'Sans référence',
            'date_du' => $planning->date_du?->format('d/m/Y'),
            'date_au' => $planning->date_au?->format('d/m/Y'),
            'heure' => $planning->heure?->format('H:i'),
            'service' => $planning->service?->designation ?: 'Sans service',
            'service_id' => $planning->service_id,
            'recommended_service_ids' => collect($recommendation['alternatives'])->pluck('service_id')->values()->all(),
            'recommendation_reason' => $recommendation['reason'],
            'supplier_vehicle' => $planning->supplierVehicule?->name ?: 'Sans fournisseur véhicule',
            'supplier_client' => $planning->supplierClient?->name ?: '-',
            'clients' => $planning->planningClients
                ->map(fn ($planningClient) => $planningClient->client?->full_name)
                ->filter()
                ->values()
                ->all(),
            'point_depart' => $planning->point_depart ?: '-',
            'destination' => trim(($planning->destination?->name ?: '') . ($planning->destination?->city ? ' - ' . $planning->destination->city : '')) ?: '-',
            'site' => $planning->site ?: '-',
            'flight' => $planning->flight ?: '-',
            'nbr_personnes' => (int) ($planning->nbr_personnes ?: 0),
            'driver' => $planning->driver?->name ?: '-',
            'guide' => $planning->guide?->name ?: '-',
            'vehicule' => trim(($planning->vehicule?->matricule ?: '') . ' ' . ($planning->vehicule?->marque ?: '') . ' ' . ($planning->vehicule?->modele ?: '')) ?: '-',
            'budget' => $budget,
            'supplier_price' => $supplierPrice,
            'gross_margin' => round($budget - $supplierPrice, 2),
            'invoice_status' => $invoice ? 'facturee' : 'non_facturee',
            'invoice_label' => $invoice ? 'Facturée' : 'Non facturée',
            'invoice' => $invoiceSnapshot,
        ];
    }

    private function planningAnalyticsHierarchy(int $year, ?int $supplierVehiculeId = null): array
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfYear();
        $yearEnd = $yearStart->copy()->endOfYear();

        $financialByDay = Planning::query()
            ->selectRaw('
                DATE(date_du) as date_label,
                COUNT(*) as total_plannings,
                COALESCE(SUM(budget), 0) as total_budget,
                COALESCE(SUM(supplier_price), 0) as total_supplier_price
            ')
            ->whereBetween('date_du', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('date_label')
            ->get()
            ->keyBy('date_label');

        $clientsByDay = PlanningClient::query()
            ->join('plannings', 'planning_clients.planning_id', '=', 'plannings.id')
            ->selectRaw('
                DATE(plannings.date_du) as date_label,
                COUNT(DISTINCT planning_clients.client_id) as total_clients
            ')
            ->whereBetween('plannings.date_du', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->when($supplierVehiculeId, fn ($query) => $query->where('plannings.supplier_vehicule_id', $supplierVehiculeId))
            ->groupBy('date_label')
            ->get()
            ->keyBy('date_label');

        $monthNames = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        $emptyMetrics = [
            'total_plannings' => 0,
            'total_budget' => 0.0,
            'total_supplier_price' => 0.0,
            'gross_margin' => 0.0,
            'total_clients' => 0,
        ];

        $metricForDate = function (Carbon $date) use ($financialByDay, $clientsByDay, $emptyMetrics) {
            $key = $date->toDateString();
            $financial = $financialByDay->get($key);
            $clients = $clientsByDay->get($key);
            $budget = round((float) ($financial?->total_budget ?? 0), 2);
            $supplierPrice = round((float) ($financial?->total_supplier_price ?? 0), 2);

            return [
                ...$emptyMetrics,
                'total_plannings' => (int) ($financial?->total_plannings ?? 0),
                'total_budget' => $budget,
                'total_supplier_price' => $supplierPrice,
                'gross_margin' => round($budget - $supplierPrice, 2),
                'total_clients' => (int) ($clients?->total_clients ?? 0),
            ];
        };

        $sumMetrics = function ($rows) use ($emptyMetrics) {
            return collect($rows)->reduce(function ($carry, $row) {
                foreach (['total_plannings', 'total_budget', 'total_supplier_price', 'gross_margin', 'total_clients'] as $metric) {
                    $carry[$metric] += (float) ($row[$metric] ?? 0);
                }

                $carry['total_plannings'] = (int) $carry['total_plannings'];
                $carry['total_clients'] = (int) $carry['total_clients'];
                $carry['total_budget'] = round((float) $carry['total_budget'], 2);
                $carry['total_supplier_price'] = round((float) $carry['total_supplier_price'], 2);
                $carry['gross_margin'] = round((float) $carry['gross_margin'], 2);

                return $carry;
            }, $emptyMetrics);
        };

        return collect(range(1, 12))
            ->map(function ($month) use ($year, $monthNames, $metricForDate, $sumMetrics) {
                $monthStart = Carbon::create($year, $month, 1)->startOfDay();
                $monthEnd = $monthStart->copy()->endOfMonth();
                $days = collect(CarbonPeriod::create($monthStart, $monthEnd))
                    ->map(function ($date) use ($metricForDate) {
                        $date = Carbon::parse($date);

                        return [
                            'date' => $date->toDateString(),
                            'label' => $date->format('d/m'),
                            'day_label' => $date->translatedFormat('D d/m'),
                            ...$metricForDate($date),
                        ];
                    })
                    ->values();

                $weeks = $days
                    ->chunk(7)
                    ->values()
                    ->map(function ($weekDays, $index) use ($sumMetrics) {
                        $metrics = $sumMetrics($weekDays);

                        return [
                            'index' => $index + 1,
                            'label' => 'Semaine ' . ($index + 1),
                            'range_label' => $weekDays->first()['label'] . ' - ' . $weekDays->last()['label'],
                            'days' => $weekDays->values(),
                            ...$metrics,
                        ];
                    })
                    ->values();

                return [
                    'month' => $month,
                    'year' => $year,
                    'label' => $monthNames[$month],
                    'short_label' => mb_substr($monthNames[$month], 0, 3),
                    'weeks' => $weeks,
                    ...$sumMetrics($days),
                ];
            })
            ->values()
            ->all();
    }

    private function financialTotals(string $dateFrom, string $dateTo, ?int $supplierVehiculeId = null): array
    {
        $totals = Planning::query()
            ->selectRaw('
                COALESCE(SUM(budget), 0) as total_budget,
                COALESCE(SUM(supplier_price), 0) as total_supplier_price
            ')
            ->whereBetween('date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->first();

        $budget = round((float) ($totals?->total_budget ?? 0), 2);
        $supplierPrice = round((float) ($totals?->total_supplier_price ?? 0), 2);

        return [
            'total_budget' => $budget,
            'total_supplier_price' => $supplierPrice,
            'gross_margin' => round($budget - $supplierPrice, 2),
            'supplier_payments' => $this->supplierPaymentTotal($dateFrom, $dateTo, $supplierVehiculeId),
        ];
    }

    private function supplierPaymentTotal(string $dateFrom, string $dateTo, ?int $supplierVehiculeId = null): float
    {
        return round((float) SupplierVehiculeInvoicePayment::query()
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->whereHas(
                'invoice',
                fn ($invoiceQuery) => $invoiceQuery->where('supplier_vehicule_id', $supplierVehiculeId)
            ))
            ->sum('amount'), 2);
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return abs($current) < 0.01 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function trendDirection(float $current, float $previous): string
    {
        if (abs($current - $previous) < 0.01) {
            return 'stable';
        }

        return $current > $previous ? 'up' : 'down';
    }

    private function vehicleEfficiency(string $dateFrom, string $dateTo, ?int $supplierVehiculeId = null): array
    {
        $vehicles = Planning::query()
            ->selectRaw('
                plannings.vehicule_id,
                COUNT(*) as total_trips,
                COALESCE(SUM(plannings.budget), 0) as total_budget,
                COALESCE(SUM(plannings.supplier_price), 0) as total_supplier_price,
                GROUP_CONCAT(DISTINCT drivers.name ORDER BY drivers.name SEPARATOR ", ") as driver_names
            ')
            ->leftJoin('drivers', 'plannings.driver_id', '=', 'drivers.id')
            ->with('vehicule:id,matricule,marque,modele,type')
            ->whereBetween('plannings.date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('plannings.supplier_vehicule_id', $supplierVehiculeId))
            ->whereNotNull('plannings.vehicule_id')
            ->groupBy('plannings.vehicule_id')
            ->get()
            ->keyBy('vehicule_id');

        $roadSheetTotals = DB::table('road_sheet_lines')
            ->join('road_sheets', 'road_sheet_lines.road_sheet_id', '=', 'road_sheets.id')
            ->join('plannings', 'road_sheets.planning_id', '=', 'plannings.id')
            ->selectRaw('
                plannings.vehicule_id,
                COALESCE(SUM(road_sheet_lines.distance), 0) as total_distance,
                COALESCE(SUM(road_sheet_lines.gasoline), 0) as road_sheet_fuel_amount,
                COALESCE(SUM(road_sheet_lines.jawaz), 0) as jawaz_amount,
                COALESCE(SUM(road_sheet_lines.other_expenses), 0) as other_expenses
            ')
            ->whereBetween('plannings.date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('plannings.supplier_vehicule_id', $supplierVehiculeId))
            ->whereNotNull('plannings.vehicule_id')
            ->groupBy('plannings.vehicule_id')
            ->get()
            ->keyBy('vehicule_id');

        $fuelLinks = DB::table('driver_fuel_invoice_plannings')
            ->join('driver_fuel_invoices', 'driver_fuel_invoice_plannings.driver_fuel_invoice_id', '=', 'driver_fuel_invoices.id')
            ->join('plannings', 'driver_fuel_invoice_plannings.planning_id', '=', 'plannings.id')
            ->select([
                'driver_fuel_invoice_plannings.driver_fuel_invoice_id',
                'plannings.vehicule_id',
                'driver_fuel_invoices.total_amount',
            ])
            ->whereBetween('plannings.date_du', [$dateFrom, $dateTo])
            ->when($supplierVehiculeId, fn ($query) => $query->where('plannings.supplier_vehicule_id', $supplierVehiculeId))
            ->where('driver_fuel_invoice_plannings.is_selected', true)
            ->whereNotNull('plannings.vehicule_id')
            ->get();

        $linksByInvoice = $fuelLinks->groupBy('driver_fuel_invoice_id');
        $fuelByVehicle = [];

        foreach ($linksByInvoice as $links) {
            $linkCount = max($links->count(), 1);
            $share = (float) $links->first()->total_amount / $linkCount;

            foreach ($links as $link) {
                $fuelByVehicle[$link->vehicule_id] = ($fuelByVehicle[$link->vehicule_id] ?? 0) + $share;
            }
        }

        $rows = $vehicles
            ->map(function ($item, $vehicleId) use ($roadSheetTotals, $fuelByVehicle) {
                $road = $roadSheetTotals->get($vehicleId);
                $distance = (float) ($road?->total_distance ?? 0);
                $roadSheetFuel = (float) ($road?->road_sheet_fuel_amount ?? 0);
                $allocatedFuel = (float) ($fuelByVehicle[$vehicleId] ?? 0);
                $fuelCost = $allocatedFuel > 0 ? $allocatedFuel : $roadSheetFuel;
                $trips = (int) $item->total_trips;

                return [
                    'id' => (int) $vehicleId,
                    'name' => trim(collect([
                        $item->vehicule?->matricule,
                        $item->vehicule?->marque,
                        $item->vehicule?->modele,
                    ])->filter()->join(' - ')) ?: 'Véhicule #' . $vehicleId,
                    'drivers' => $item->driver_names ?: '-',
                    'type' => $item->vehicule?->type,
                    'total_trips' => $trips,
                    'total_distance' => round($distance, 2),
                    'fuel_cost' => round($fuelCost, 2),
                    'road_sheet_fuel_amount' => round($roadSheetFuel, 2),
                    'invoice_fuel_amount' => round($allocatedFuel, 2),
                    'jawaz_amount' => round((float) ($road?->jawaz_amount ?? 0), 2),
                    'other_expenses' => round((float) ($road?->other_expenses ?? 0), 2),
                    'fuel_cost_per_km' => $distance > 0 ? round($fuelCost / $distance, 2) : null,
                    'fuel_cost_per_trip' => $trips > 0 ? round($fuelCost / $trips, 2) : null,
                    'total_budget' => round((float) $item->total_budget, 2),
                    'total_supplier_price' => round((float) $item->total_supplier_price, 2),
                ];
            })
            ->sortByDesc('total_trips')
            ->values();

        $withKm = $rows->filter(fn ($row) => $row['total_distance'] > 0 && $row['fuel_cost'] > 0);

        return [
            'summary' => [
                'vehicles_count' => $rows->count(),
                'total_trips' => (int) $rows->sum('total_trips'),
                'total_distance' => round((float) $rows->sum('total_distance'), 2),
                'total_fuel_cost' => round((float) $rows->sum('fuel_cost'), 2),
                'average_fuel_cost_per_km' => $withKm->sum('total_distance') > 0
                    ? round($withKm->sum('fuel_cost') / $withKm->sum('total_distance'), 2)
                    : null,
            ],
            'best_vehicle' => $withKm->sortBy('fuel_cost_per_km')->first(),
            'worst_vehicle' => $withKm->sortByDesc('fuel_cost_per_km')->first(),
            'vehicles' => $rows->take(12)->values(),
        ];
    }
}
