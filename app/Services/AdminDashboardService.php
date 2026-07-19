<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\VehicleMaintenance;
use App\Models\Vehicule;
use App\Support\MobilePlanningSerializer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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

        $alerts = collect([
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
            'alerts' => $alerts,
            'recent_plannings' => $recent,
        ];
    }
}
