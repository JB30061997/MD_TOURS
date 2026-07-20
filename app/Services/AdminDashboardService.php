<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Driver;
use App\Models\DriverFuelInvoice;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\RoadSheet;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeInvoice;
use App\Models\VehicleMaintenance;
use App\Models\Vehicule;
use App\Support\MobilePlanningSerializer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function period(string $filter = 'month', ?string $from = null, ?string $to = null): array
    {
        $now = now();

        [$start, $end] = match ($filter) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfMonth(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        abort_if($start->greaterThan($end), 422, 'La date de début doit précéder la date de fin.');

        return [$start->toDateString(), $end->toDateString()];
    }

    public function snapshot(string $filter = 'month', ?string $from = null, ?string $to = null): array
    {
        [$dateFrom, $dateTo] = $this->period($filter, $from, $to);
        $period = fn (): Builder => Planning::query()->whereBetween('date_du', [$dateFrom, $dateTo]);
        $today = now()->toDateString();

        $stats = [
            'plannings_total' => $period()->count(),
            'plannings_today' => Planning::query()->whereDate('date_du', $today)->count(),
            'plannings_upcoming' => Planning::query()->whereDate('date_du', '>', $today)->count(),
            'without_service' => $period()->whereNull('service_id')->count(),
            'without_supplier' => $period()->whereNull('supplier_vehicule_id')->count(),
            'without_driver' => $period()->whereNull('driver_id')->count(),
            'commandes' => Commande::query()->whereBetween('date', [$dateFrom, $dateTo])->count(),
            'road_sheets_pending' => $period()->where(function (Builder $query) {
                $query->whereDoesntHave('roadSheet')
                    ->orWhereHas('roadSheet', fn (Builder $roadSheet) => $roadSheet->where('status', '!=', 'renseignee'));
            })->count(),
            'vehicles' => Vehicule::count(),
            'drivers' => Driver::count(),
            'clients' => Client::count(),
            'suppliers' => SupplierClient::count() + SupplierVehicule::count(),
        ];

        $revenue = round((float) $period()->sum('budget'), 2);
        $plannedSupplierCost = round((float) $period()->sum('supplier_price'), 2);
        $uninvoicedSupplierCost = round((float) $period()
            ->where('supplier_price', '>', 0)
            ->whereDoesntHave('supplierVehiculeInvoicePlannings')
            ->sum('supplier_price'), 2);

        $supplierInvoices = SupplierVehiculeInvoice::query()
            ->withSum('payments as paid_amount', 'amount')
            ->where(function (Builder $query) use ($dateFrom, $dateTo) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->orWhere(function (Builder $periodQuery) use ($dateFrom, $dateTo) {
                        $periodQuery->whereDate('period_start', '<=', $dateTo)
                            ->whereDate('period_end', '>=', $dateFrom);
                    });
            })
            ->get();

        $supplierInvoiced = round((float) $supplierInvoices->sum('total_amount'), 2);
        $supplierPaid = round((float) $supplierInvoices->sum('paid_amount'), 2);
        $supplierPending = max(round($supplierInvoiced - $supplierPaid, 2), 0);
        $fuelExpenses = round((float) DriverFuelInvoice::query()
            ->where(function (Builder $query) use ($dateFrom, $dateTo) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->orWhere(function (Builder $periodQuery) use ($dateFrom, $dateTo) {
                        $periodQuery->whereDate('period_start', '<=', $dateTo)
                            ->whereDate('period_end', '>=', $dateFrom);
                    });
            })
            ->sum('total_amount'), 2);
        $maintenanceExpenses = round((float) VehicleMaintenance::query()
            ->whereBetween('date_maintenance', [$dateFrom, $dateTo])
            ->sum('montant'), 2);
        $expenses = round($plannedSupplierCost + $fuelExpenses + $maintenanceExpenses, 2);
        $grossMargin = round($revenue - $expenses, 2);
        $marginRate = $revenue > 0 ? round(($grossMargin / $revenue) * 100, 2) : 0.0;

        $financial = [
            'revenue' => $revenue,
            'planned_supplier_cost' => $plannedSupplierCost,
            'supplier_invoiced' => $supplierInvoiced,
            'supplier_uninvoiced' => $uninvoicedSupplierCost,
            'supplier_paid' => $supplierPaid,
            'supplier_pending' => $supplierPending,
            'fuel_expenses' => $fuelExpenses,
            'maintenance_expenses' => $maintenanceExpenses,
            'expenses' => $expenses,
            'gross_margin' => $grossMargin,
            'margin_rate' => $marginRate,
        ];

        $topSuppliers = $this->topByRelation($dateFrom, $dateTo, 'supplier_vehicule_id', 'supplierVehicule', 'name');
        $topDrivers = $this->topByRelation($dateFrom, $dateTo, 'driver_id', 'driver', 'name');
        $topVehicles = $this->topByRelation($dateFrom, $dateTo, 'vehicule_id', 'vehicule', 'matricule');
        $topClients = PlanningClient::query()
            ->join('plannings', 'planning_clients.planning_id', '=', 'plannings.id')
            ->join('clients', 'planning_clients.client_id', '=', 'clients.id')
            ->whereBetween('plannings.date_du', [$dateFrom, $dateTo])
            ->selectRaw('clients.id, clients.full_name, COUNT(DISTINCT plannings.id) as total')
            ->groupBy('clients.id', 'clients.full_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->full_name, 'total' => (int) $row->total])
            ->values()
            ->all();

        $monthlyPerformance = Planning::query()
            ->whereBetween('date_du', [$dateFrom, $dateTo])
            ->get()
            ->groupBy(fn (Planning $planning) => $planning->date_du?->format('Y-m'))
            ->sortKeys()
            ->map(function ($plannings, $monthKey) {
                $monthRevenue = round((float) $plannings->sum('budget'), 2);
                $monthSupplierCost = round((float) $plannings->sum('supplier_price'), 2);

                return [
                    'month' => $monthKey,
                    'label' => Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('M Y'),
                    'operations' => $plannings->count(),
                    'revenue' => $monthRevenue,
                    'supplier_cost' => $monthSupplierCost,
                    'margin' => round($monthRevenue - $monthSupplierCost, 2),
                ];
            })
            ->values()
            ->all();

        $alerts = collect([
            ['id' => 'negative_margin', 'label' => 'Marge négative', 'value' => $grossMargin < 0 ? abs($grossMargin) : 0, 'module' => 'dashboard', 'route' => 'Dashboard', 'tone' => 'red', 'kind' => 'financial'],
            ['id' => 'supplier_pending', 'label' => 'Paiements fournisseurs en attente', 'value' => $supplierPending, 'module' => 'supplier_invoices', 'route' => 'AdminSupplierVehiculeInvoices', 'tone' => 'orange', 'kind' => 'financial'],
            ['id' => 'supplier_uninvoiced', 'label' => 'Coûts fournisseurs non facturés', 'value' => $uninvoicedSupplierCost, 'module' => 'supplier_invoices', 'route' => 'AdminSupplierVehiculeInvoices', 'tone' => 'violet', 'kind' => 'financial'],
            ['id' => 'without_service', 'label' => 'Sans service', 'value' => $stats['without_service'], 'module' => 'plannings', 'route' => 'AdminPlannings', 'filter' => 'without_service', 'tone' => 'violet'],
            ['id' => 'without_supplier', 'label' => 'Sans fournisseur', 'value' => $stats['without_supplier'], 'module' => 'plannings', 'route' => 'AdminPlannings', 'filter' => 'without_supplier', 'tone' => 'orange'],
            ['id' => 'without_driver', 'label' => 'Sans chauffeur', 'value' => $stats['without_driver'], 'module' => 'plannings', 'route' => 'AdminPlannings', 'filter' => 'without_driver', 'tone' => 'red'],
            ['id' => 'road_sheets_pending', 'label' => 'Fiches à compléter', 'value' => $stats['road_sheets_pending'], 'module' => 'road_sheets', 'route' => 'AdminPlannings', 'filter' => 'road_sheets_pending', 'tone' => 'blue'],
            ['id' => 'maintenance_planned', 'label' => 'Maintenances planifiées', 'value' => VehicleMaintenance::query()->where('status', 'planifie')->count(), 'module' => 'maintenance', 'route' => 'AdminVehicleMaintenances', 'tone' => 'green'],
        ])->filter(fn (array $alert) => $alert['value'] > 0)->values()->all();

        $recent = $period()
            ->with(MobilePlanningSerializer::relations())
            ->latest('date_du')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Planning $planning) => MobilePlanningSerializer::enrich($planning))
            ->values()
            ->all();

        return [
            'filter' => $filter,
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'generated_at' => now()->toIso8601String(),
            'stats' => $stats,
            'financial' => $financial,
            'performance' => ['monthly' => $monthlyPerformance],
            'top' => [
                'suppliers' => $topSuppliers,
                'drivers' => $topDrivers,
                'vehicles' => $topVehicles,
                'clients' => $topClients,
            ],
            'alerts' => $alerts,
            'recent_plannings' => $recent,
        ];
    }

    private function topByRelation(string $dateFrom, string $dateTo, string $foreignKey, string $relation, string $labelColumn): array
    {
        return Planning::query()
            ->whereBetween('date_du', [$dateFrom, $dateTo])
            ->whereNotNull($foreignKey)
            ->select($foreignKey, DB::raw('COUNT(*) as total'))
            ->with($relation)
            ->groupBy($foreignKey)
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($relation, $labelColumn, $foreignKey) {
                $related = $row->{$relation};

                return [
                    'id' => (int) $row->{$foreignKey},
                    'name' => (string) ($related?->{$labelColumn} ?: 'Non renseigné'),
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }
}
