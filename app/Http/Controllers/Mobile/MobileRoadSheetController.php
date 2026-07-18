<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Support\MobilePlanningSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileRoadSheetController extends Controller
{
    public function index(Request $request)
    {
        $driver = $this->driverFor($request);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:saved'],
            'recorded' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $savedOnly = ($validated['status'] ?? null) === 'saved' || (bool) ($validated['recorded'] ?? false);
        $date = $validated['date'] ?? ($savedOnly ? null : today()->toDateString());

        $plannings = $this->basePlanningQuery()
            ->where('driver_id', $driver->id)
            ->when($date, function ($query) use ($date) {
                $query->whereDate('date_du', $date)
                    ->orWhere(function ($rangeQuery) use ($date) {
                        $rangeQuery
                            ->whereDate('date_du', '<=', $date)
                            ->whereNotNull('date_au')
                            ->whereDate('date_au', '>=', $date);
                    });
            })
            ->when($savedOnly, fn ($query) => $query->whereHas('roadSheet', fn ($roadSheet) => $roadSheet->where('status', 'renseignee')))
            ->when(trim((string) ($validated['search'] ?? '')), function ($query) use ($validated) {
                $search = trim((string) $validated['search']);
                $query->where('ref_dossier', 'like', "%{$search}%");
            })
            ->orderBy('heure')
            ->orderBy('id')
            ->get()
            ->map(fn (Planning $planning) => $this->serializePlanning($planning));

        return response()->json([
            'date' => $date,
            'plannings' => $plannings,
        ]);
    }

    public function show(Request $request, Planning $planning)
    {
        $this->assertDriverOwnsPlanning($request, $planning);

        $planning->load($this->planningRelations());
        $roadSheet = $this->ensureRoadSheet($planning)->load('lines');

        return response()->json([
            'planning' => $this->serializePlanning($planning),
            'road_sheet' => $roadSheet,
            'totals' => $this->totals($roadSheet),
        ]);
    }

    public function store(Request $request, Planning $planning)
    {
        $this->assertDriverOwnsPlanning($request, $planning);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'pre_service_km' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_start' => ['nullable', 'required_with:pre_service_odometer_end', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_end' => ['nullable', 'required_with:pre_service_odometer_start', 'integer', 'min:0', 'max:4294967295', 'gte:pre_service_odometer_start'],
            'pre_service_origin' => ['nullable', 'string', 'max:255'],
            'pre_service_note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'uuid'],
            'lines' => ['nullable', 'array'],
            'lines.*.date' => ['nullable', 'date'],
            'lines.*.departure_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.arrival_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.distance' => ['nullable', 'integer', 'min:0'],
            'lines.*.gasoline' => ['nullable', 'numeric', 'min:0'],
            'lines.*.jawaz' => ['nullable', 'numeric', 'min:0'],
            'lines.*.other_expenses' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $planning->load($this->planningRelations());

        $roadSheet = DB::transaction(function () use ($planning, $validated) {
            $roadSheet = $this->ensureRoadSheet($planning);
            $idempotencyKey = $validated['idempotency_key'] ?? null;

            if ($idempotencyKey) {
                $conflict = RoadSheet::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->where('planning_id', '!=', $planning->id)
                    ->exists();

                abort_if($conflict, 409, 'This synchronization key is already linked to another planning.');
            }
            $lines = collect($validated['lines'] ?? [])->values();

            $preServiceKm = $this->preServiceDistance($validated, $roadSheet);
            $roadSheet->update([
                'notes' => $validated['notes'] ?? null,
                'pre_service_km' => $preServiceKm,
                'pre_service_odometer_start' => $validated['pre_service_odometer_start'] ?? null,
                'pre_service_odometer_end' => $validated['pre_service_odometer_end'] ?? null,
                'pre_service_origin' => $validated['pre_service_origin'] ?? null,
                'pre_service_note' => $validated['pre_service_note'] ?? null,
                'idempotency_key' => $idempotencyKey ?: $roadSheet->idempotency_key,
                'status' => $lines->isEmpty() ? 'a_completer' : 'renseignee',
            ]);

            $roadSheet->lines()->delete();

            $lines->each(function (array $line, int $index) use ($roadSheet) {
                $departure = (int) ($line['departure_kms'] ?? 0);
                $arrival = (int) ($line['arrival_kms'] ?? 0);
                $distance = max(0, $arrival - $departure);

                $roadSheet->lines()->create([
                    'date' => $line['date'] ?? null,
                    'departure_kms' => $departure ?: null,
                    'arrival_kms' => $arrival ?: null,
                    'distance' => $distance ?: null,
                    'gasoline' => $line['gasoline'] ?? null,
                    'jawaz' => $line['jawaz'] ?? null,
                    'other_expenses' => $line['other_expenses'] ?? null,
                    'notes' => $line['notes'] ?? null,
                    'sort_order' => $line['sort_order'] ?? $index,
                ]);
            });

            return $roadSheet->fresh('lines');
        });

        return response()->json([
            'message' => 'Road sheet saved.',
            'planning' => $this->serializePlanning($planning),
            'road_sheet' => $roadSheet,
            'totals' => $this->totals($roadSheet),
        ]);
    }

    private function basePlanningQuery()
    {
        return Planning::query()->with($this->planningRelations());
    }

    private function planningRelations(): array
    {
        return [...MobilePlanningSerializer::relations(), 'roadSheet.lines'];
    }

    private function driverFor(Request $request): Driver
    {
        $user = $request->user();

        abort_unless($user, 401);

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        abort_unless(in_array('driver', $roles, true), 403, 'Driver access only.');

        $driver = Driver::where('user_id', $user->id)->first();

        abort_unless($driver, 403, 'Driver profile is not linked to this account.');

        return $driver;
    }

    private function assertDriverOwnsPlanning(Request $request, Planning $planning): Driver
    {
        $driver = $this->driverFor($request);

        abort_unless((int) $planning->driver_id === (int) $driver->id, 403, 'This planning is not assigned to your driver account.');

        return $driver;
    }

    private function ensureRoadSheet(Planning $planning): RoadSheet
    {
        return RoadSheet::firstOrCreate(
            ['planning_id' => $planning->id],
            [
                'voucher_number' => $planning->ref_dossier,
                'start_city' => $planning->point_depart,
                'end_city' => $planning->destination?->city ?: $planning->destination?->name,
                'start_flight' => $planning->flight,
                'end_flight' => $planning->flight,
                'start_time' => $planning->heure,
                'end_time' => $planning->heure,
                'signature_date' => today(),
                'signature_name' => $planning->driver?->name,
                'status' => 'a_completer',
            ]
        );
    }

    private function serializePlanning(Planning $planning): Planning
    {
        $planning->setAttribute('road_sheet_status', $planning->roadSheet?->status ?? 'a_completer');
        $planning->setAttribute('road_sheet_status_label', $planning->roadSheet?->status_label ?? 'À compléter');

        return MobilePlanningSerializer::enrich($planning);
    }

    private function totals(RoadSheet $roadSheet): array
    {
        $lines = $roadSheet->relationLoaded('lines')
            ? $roadSheet->lines
            : $roadSheet->lines()->get();

        return [
            'pre_service_km' => (int) $roadSheet->pre_service_km,
            'pre_service_odometer_start' => $roadSheet->pre_service_odometer_start,
            'pre_service_odometer_end' => $roadSheet->pre_service_odometer_end,
            'circuit_distance' => (int) $lines->sum('distance'),
            'total_distance' => (int) $roadSheet->pre_service_km + (int) $lines->sum('distance'),
            'total_gasoline' => (float) $lines->sum('gasoline'),
            'total_jawaz' => (float) $lines->sum('jawaz'),
            'total_expenses' => (float) $lines->sum(fn ($line) => $line->gasoline + $line->jawaz + $line->other_expenses),
        ];
    }

    private function preServiceDistance(array $data, RoadSheet $roadSheet): int
    {
        if (isset($data['pre_service_odometer_start'], $data['pre_service_odometer_end'])) {
            return (int) $data['pre_service_odometer_end'] - (int) $data['pre_service_odometer_start'];
        }

        return (int) ($data['pre_service_km'] ?? $roadSheet->pre_service_km ?? 0);
    }
}
