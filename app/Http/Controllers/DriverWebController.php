<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Support\RoadSheetDurationResolver;
use App\Services\DriverPlanningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DriverWebController extends Controller
{
    public function __construct(private readonly DriverPlanningService $driverPlannings)
    {
    }

    public function dashboard(Request $request)
    {
        $driver = $this->driver($request);
        $plannings = $this->planningQuery($driver)->get();

        return Inertia::render('Driver/Dashboard', [
            'driver' => $driver,
            'plannings' => $plannings,
            'stats' => $this->driverPlannings->stats($driver),
            'today' => today()->toDateString(),
        ]);
    }

    public function profile(Request $request)
    {
        return Inertia::render('Driver/Profile', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
        ]);
    }

    public function plannings(Request $request)
    {
        $driver = $this->driver($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'in:past,today,upcoming'],
            'date' => ['nullable', 'date'],
        ]);

        $query = $this->planningQuery($driver);
        $this->applyFilters($query, $filters);

        return Inertia::render('Driver/Plannings', [
            'plannings' => $query->paginate(18)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function planning(Request $request, Planning $planning)
    {
        $this->authorizePlanning($request, $planning);
        $planning->load($this->relations());

        return Inertia::render('Driver/PlanningDetails', ['planning' => $planning]);
    }

    public function roadSheets(Request $request)
    {
        return $this->roadSheetList($request, false);
    }

    public function savedRoadSheets(Request $request)
    {
        return $this->roadSheetList($request, true);
    }

    public function editRoadSheet(Request $request, Planning $planning)
    {
        $this->authorizePlanning($request, $planning);
        $planning->load($this->relations());
        $roadSheet = $this->ensureRoadSheet($planning);

        return Inertia::render('Driver/RoadSheetForm', [
            'planning' => $planning->fresh($this->relations()),
            'roadSheet' => $roadSheet,
        ]);
    }

    public function updateRoadSheet(Request $request, Planning $planning)
    {
        $this->authorizePlanning($request, $planning);
        $data = $request->validate([
            'pre_service_km' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_start' => ['nullable', 'required_with:pre_service_odometer_end', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_end' => ['nullable', 'required_with:pre_service_odometer_start', 'integer', 'min:0', 'max:4294967295', 'gte:pre_service_odometer_start'],
            'pre_service_origin' => ['nullable', 'string', 'max:255'],
            'pre_service_note' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.date' => ['required', 'date', 'distinct'],
            'lines.*.departure_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.arrival_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.gasoline' => ['nullable', 'numeric', 'min:0'],
            'lines.*.jawaz' => ['nullable', 'numeric', 'min:0'],
            'lines.*.other_expenses' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($planning, $data) {
            $roadSheet = RoadSheet::firstOrCreate(
                ['planning_id' => $planning->id],
                ['voucher_number' => $planning->ref_dossier, 'status' => 'a_completer']
            );
            $preServiceKm = isset($data['pre_service_odometer_start'], $data['pre_service_odometer_end'])
                ? (int) $data['pre_service_odometer_end'] - (int) $data['pre_service_odometer_start']
                : (int) ($data['pre_service_km'] ?? $roadSheet->pre_service_km ?? 0);
            $roadSheet->update([
                'pre_service_km' => $preServiceKm,
                'pre_service_odometer_start' => $data['pre_service_odometer_start'] ?? null,
                'pre_service_odometer_end' => $data['pre_service_odometer_end'] ?? null,
                'pre_service_origin' => $data['pre_service_origin'] ?? null,
                'pre_service_note' => $data['pre_service_note'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'renseignee',
            ]);
            $roadSheet->lines()->delete();
            foreach (array_values($data['lines']) as $index => $line) {
                $departure = (int) ($line['departure_kms'] ?? 0);
                $arrival = (int) ($line['arrival_kms'] ?? 0);
                $roadSheet->lines()->create([
                    'date' => $line['date'],
                    'departure_kms' => $departure ?: null,
                    'arrival_kms' => $arrival ?: null,
                    'distance' => $arrival >= $departure ? $arrival - $departure : 0,
                    'gasoline' => $line['gasoline'] ?? null,
                    'jawaz' => $line['jawaz'] ?? null,
                    'other_expenses' => $line['other_expenses'] ?? null,
                    'notes' => $line['notes'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('driver.road-sheets.saved')->with('success', 'Fiche de route enregistrée.');
    }

    private function roadSheetList(Request $request, bool $saved)
    {
        $driver = $this->driver($request);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'date' => ['nullable', 'date']]);
        $query = $this->planningQuery($driver)
            ->when($saved, fn ($q) => $q->whereHas('roadSheet', fn ($road) => $road->where('status', 'renseignee')))
            ->when(! $saved, fn ($q) => $q->where(fn ($inner) => $inner->whereDoesntHave('roadSheet')->orWhereHas('roadSheet', fn ($road) => $road->where('status', '!=', 'renseignee'))));
        $this->applyFilters($query, $filters);

        return Inertia::render('Driver/RoadSheets', [
            'plannings' => $query->paginate(18)->withQueryString(),
            'filters' => $filters,
            'saved' => $saved,
        ]);
    }

    private function driver(Request $request): Driver
    {
        $driver = Driver::where('user_id', $request->user()->id)->first();
        abort_unless($driver, 403, 'Profil chauffeur introuvable.');
        return $driver;
    }

    private function authorizePlanning(Request $request, Planning $planning): void
    {
        abort_unless((int) $planning->driver_id === (int) $this->driver($request)->id, 403);
    }

    private function planningQuery(Driver $driver)
    {
        return $this->driverPlannings->query($driver, $this->relations())
            ->orderByDesc('date_du')->orderByDesc('heure')->orderByDesc('id');
    }

    private function relations(): array
    {
        return ['service', 'destination', 'vehicule', 'supplierVehicule', 'driver', 'guide', 'planningClients.client.supplierClient', 'roadSheet.lines'];
    }

    private function applyFilters($query, array $filters): void
    {
        $today = today()->toDateString();
        $this->driverPlannings->applyPeriod($query, $filters['period'] ?? null, $today);
        $query->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('date_du', $date))
          ->when(trim((string) ($filters['search'] ?? '')), function ($q) use ($filters) {
              $search = trim($filters['search']);
              $q->where(fn ($x) => $x->where('ref_dossier', 'like', "%{$search}%")->orWhere('point_depart', 'like', "%{$search}%")->orWhereHas('destination', fn ($d) => $d->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))->orWhereHas('planningClients.client', fn ($c) => $c->where('full_name', 'like', "%{$search}%")));
          });
    }

    private function ensureRoadSheet(Planning $planning): RoadSheet
    {
        $roadSheet = RoadSheet::firstOrCreate(['planning_id' => $planning->id], ['voucher_number' => $planning->ref_dossier, 'start_city' => $planning->point_depart, 'end_city' => $planning->destination?->city ?: $planning->destination?->name, 'status' => 'a_completer']);
        $start = $planning->date_du?->copy()->startOfDay();
        if ($start && $roadSheet->lines()->count() === 0) {
            for ($day = 0; $day < RoadSheetDurationResolver::resolve($planning); $day++) $roadSheet->lines()->create(['date' => $start->copy()->addDays($day)->toDateString(), 'sort_order' => $day]);
        }
        return $roadSheet->fresh('lines');
    }

}
