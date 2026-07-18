<?php

namespace App\Http\Controllers;

use App\Mail\RoadSheetPdfMail;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Support\RoadSheetDurationResolver;
use App\Services\RoadSheetOperationalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class RoadSheetController extends Controller
{
    public function index(Request $request, RoadSheetOperationalService $roadSheets)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'in:pending,partial,completed'],
            'sort' => ['nullable', 'in:start_date,end_date,driver,remaining_days,real_total_distance,status'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $sort = $filters['sort'] ?? 'start_date';
        $direction = $filters['direction'] ?? 'desc';

        $query = Planning::query()->select('plannings.*')->distinct()->with([
            'roadSheet.lines',
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client',
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ref_dossier', 'like', "%{$search}%")
                        ->orWhere('flight', 'like', "%{$search}%")
                        ->orWhere('point_depart', 'like', "%{$search}%")
                        ->orWhereHas('driver', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn ($sub) => $sub->where('designation', 'like', "%{$search}%"))
                        ->orWhereHas('destination', fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))
                        ->orWhereHas('guide', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplierVehicule', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('planningClients.client', fn ($sub) => $sub->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['driver_id'] ?? null, fn ($query, $driverId) => $query->where('driver_id', $driverId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date_du', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date_au', '<=', $date));

        $computedSort = in_array($sort, ['remaining_days', 'real_total_distance', 'status'], true);
        $needsComputedCollection = $computedSort || ! empty($filters['status']);

        if ($needsComputedCollection) {
            $items = $query->get()->map(function (Planning $planning) use ($roadSheets) {
                $planning->setAttribute('road_sheet_summary', $roadSheets->summarize($planning));
                return $planning;
            });
            if (! empty($filters['status'])) {
                $items = $items->where('road_sheet_summary.global_status', $filters['status']);
            }
            $sortKey = match ($sort) {
                'remaining_days' => 'road_sheet_summary.remaining_days',
                'real_total_distance' => 'road_sheet_summary.real_total_distance',
                'status' => 'road_sheet_summary.global_status',
                default => 'date_du',
            };
            $items = $direction === 'asc' ? $items->sortBy($sortKey) : $items->sortByDesc($sortKey);
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 20;
            $plannings = new LengthAwarePaginator(
                $items->forPage($page, $perPage)->values(),
                $items->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            match ($sort) {
                'end_date' => $query->orderBy('date_au', $direction),
                'driver' => $query->orderBy(
                    \App\Models\Driver::select('name')->whereColumn('drivers.id', 'plannings.driver_id'),
                    $direction
                ),
                default => $query->orderBy('date_du', $direction),
            };
            $plannings = $query->orderByDesc('id')->paginate(20)->withQueryString();
            $plannings->getCollection()->transform(function (Planning $planning) use ($roadSheets) {
                $planning->setAttribute('road_sheet_summary', $roadSheets->summarize($planning));
                return $planning;
            });
        }

        return Inertia::render('RoadSheets/Index', [
            'plannings' => $plannings,
            'filters' => [...$filters, 'search' => $search, 'sort' => $sort, 'direction' => $direction],
            'drivers' => \App\Models\Driver::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Planning $planning)
    {
        $planning->load([
            'roadSheet.lines',
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client',
        ]);

        $roadSheet = $this->roadSheetFromPlanning($planning);

        $roadSheet->load('lines');

        return Inertia::render('RoadSheets/Show', [
            'planning' => $planning->fresh([
                'supplierVehicule',
                'driver',
                'guide',
                'service',
                'destination',
                'vehicule',
                'planningClients.client',
            ]),
            'roadSheet' => $roadSheet,
        ]);
    }

    public function pdf(Planning $planning)
    {
        $roadSheet = $this->roadSheetFromPlanning($planning);

        return $this->buildPdf($roadSheet)->stream($this->pdfFileName($roadSheet));
    }

    public function sendEmail(Planning $planning)
    {
        $roadSheet = $this->roadSheetFromPlanning($planning);
        $recipient = $this->planningRecipient($planning);

        if (!$this->hasValidRecipientEmail($recipient?->email)) {
            return back()->with('error', 'Aucune adresse email valide n’est renseignée pour ce fournisseur.');
        }

        try {
            Mail::to($recipient->email)->send(
                new RoadSheetPdfMail(
                    $roadSheet,
                    $this->buildPdf($roadSheet)->output(),
                    $this->pdfFileName($roadSheet)
                )
            );
        } catch (\Throwable $e) {
            Log::warning('Roadsheet email send failed', [
                'road_sheet_id' => $roadSheet->id,
                'planning_id' => $planning->id,
                'recipient' => $recipient->email,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'La roadsheet n’a pas pu être envoyée. Vérifiez l’adresse email du fournisseur ou la configuration SMTP.');
        }

        return back()->with('success', 'Roadsheet envoyée au fournisseur avec succès.');
    }

    public function update(Request $request, RoadSheet $roadSheet)
    {
        $data = $request->validate([
            'voucher_number' => ['nullable', 'string', 'max:255'],
            'pre_service_km' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_start' => ['nullable', 'required_with:pre_service_odometer_end', 'integer', 'min:0', 'max:4294967295'],
            'pre_service_odometer_end' => ['nullable', 'required_with:pre_service_odometer_start', 'integer', 'min:0', 'max:4294967295', 'gte:pre_service_odometer_start'],
            'pre_service_origin' => ['nullable', 'string', 'max:255'],
            'pre_service_note' => ['nullable', 'string', 'max:500'],
            'start_city' => ['nullable', 'string', 'max:255'],
            'end_city' => ['nullable', 'string', 'max:255'],
            'start_flight' => ['nullable', 'string', 'max:255'],
            'end_flight' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'signature_date' => ['nullable', 'date'],
            'signature_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.date' => ['nullable', 'date'],
            'lines.*.departure_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.arrival_kms' => ['nullable', 'integer', 'min:0'],
            'lines.*.distance' => ['nullable', 'integer', 'min:0'],
            'lines.*.gasoline' => ['nullable', 'numeric', 'min:0'],
            'lines.*.jawaz' => ['nullable', 'numeric', 'min:0'],
            'lines.*.other_expenses' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $roadSheet) {
            $preServiceKm = isset($data['pre_service_odometer_start'], $data['pre_service_odometer_end'])
                ? (int) $data['pre_service_odometer_end'] - (int) $data['pre_service_odometer_start']
                : (int) ($data['pre_service_km'] ?? $roadSheet->pre_service_km ?? 0);
            $roadSheet->update([
                'voucher_number' => $data['voucher_number'] ?? null,
                'pre_service_km' => $preServiceKm,
                'pre_service_odometer_start' => $data['pre_service_odometer_start'] ?? null,
                'pre_service_odometer_end' => $data['pre_service_odometer_end'] ?? null,
                'pre_service_origin' => $data['pre_service_origin'] ?? null,
                'pre_service_note' => $data['pre_service_note'] ?? null,
                'start_city' => $data['start_city'] ?? null,
                'end_city' => $data['end_city'] ?? null,
                'start_flight' => $data['start_flight'] ?? null,
                'end_flight' => $data['end_flight'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'signature_date' => $data['signature_date'] ?? null,
                'signature_name' => $data['signature_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $roadSheet->lines()->delete();

            $lines = collect($data['lines'] ?? [])
                ->values()
                ->sortBy(fn (array $line, int $index) => sprintf(
                    '%s-%06d',
                    empty($line['date']) ? '9999-12-31' : $line['date'],
                    $index
                ))
                ->values();

            foreach ($lines as $index => $line) {
                if ($this->emptyLine($line)) {
                    continue;
                }

                $departure = $line['departure_kms'] ?? null;
                $arrival = $line['arrival_kms'] ?? null;
                $distance = $line['distance'] ?? null;

                if ($distance === null && $departure !== null && $arrival !== null && $arrival >= $departure) {
                    $distance = $arrival - $departure;
                }

                $roadSheet->lines()->create([
                    'date' => $line['date'] ?? null,
                    'departure_kms' => $departure,
                    'arrival_kms' => $arrival,
                    'distance' => $distance,
                    'gasoline' => $line['gasoline'] ?? null,
                    'jawaz' => $line['jawaz'] ?? null,
                    'other_expenses' => $line['other_expenses'] ?? null,
                    'notes' => $line['notes'] ?? null,
                    'sort_order' => $index,
                ]);
            }
            app(RoadSheetOperationalService::class)->syncStatus(
                $roadSheet->planning->setRelation('roadSheet', $roadSheet->fresh('lines'))
            );
        });

        return redirect()
            ->route('road-sheets.show', $roadSheet->planning_id)
            ->with('success', 'Fiche de route enregistrée avec succès.');
    }

    private function emptyLine(array $line): bool
    {
        foreach (['date', 'departure_kms', 'arrival_kms', 'distance', 'gasoline', 'jawaz', 'other_expenses', 'notes'] as $key) {
            if (($line[$key] ?? null) !== null && $line[$key] !== '') {
                return false;
            }
        }

        return true;
    }

    private function roadSheetFromPlanning(Planning $planning): RoadSheet
    {
        $planning->loadMissing([
            'roadSheet.lines',
            'supplierClient',
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client.supplierClient',
        ]);

        $roadSheet = $planning->roadSheet ?: RoadSheet::create([
            'planning_id' => $planning->id,
            'voucher_number' => $planning->ref_dossier,
            'start_flight' => $planning->flight,
            'end_flight' => $planning->flight,
            'start_time' => $planning->heure,
            'end_time' => $planning->heure,
            'start_city' => $planning->point_depart,
            'end_city' => $planning->destination?->city ?: $planning->destination?->name,
            'signature_date' => now()->toDateString(),
        ]);

        $this->completeRoadSheetDays($roadSheet, $planning);

        return $roadSheet->fresh([
            'lines',
            'planning.supplierClient',
            'planning.supplierVehicule',
            'planning.driver',
            'planning.guide',
            'planning.service',
            'planning.destination',
            'planning.vehicule',
            'planning.planningClients.client.supplierClient',
        ]);
    }

    private function completeRoadSheetDays(RoadSheet $roadSheet, Planning $planning): void
    {
        $startDate = $planning->date_du?->copy()->startOfDay();

        if (!$startDate) {
            return;
        }

        $duration = RoadSheetDurationResolver::resolve($planning);
        $existingDates = $roadSheet->lines()
            ->whereNotNull('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();
        $nextSortOrder = ((int) $roadSheet->lines()->max('sort_order')) + 1;

        for ($day = 0; $day < $duration; $day++) {
            $date = $startDate->copy()->addDays($day)->toDateString();

            if ($existingDates->has($date)) {
                continue;
            }

            $roadSheet->lines()->create([
                'date' => $date,
                'sort_order' => $nextSortOrder++,
            ]);
            $existingDates->put($date, true);
        }

        $roadSheet->lines()
            ->get()
            ->sortBy(fn ($line) => sprintf(
                '%s-%010d',
                $line->date?->toDateString() ?: '9999-12-31',
                $line->id
            ))
            ->values()
            ->each(function ($line, int $index) {
                if ((int) $line->sort_order !== $index) {
                    $line->update(['sort_order' => $index]);
                }
            });
    }

    private function buildPdf(RoadSheet $roadSheet)
    {
        return Pdf::loadView('pdf.road-sheet', [
            'roadSheet' => $roadSheet,
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');
    }

    private function pdfFileName(RoadSheet $roadSheet): string
    {
        return 'road-sheet-' . Str::slug($roadSheet->voucher_number ?: $roadSheet->planning?->ref_dossier ?: $roadSheet->id) . '.pdf';
    }

    private function planningRecipient(Planning $planning): SupplierVehicule|SupplierClient|null
    {
        $planning->loadMissing(['supplierVehicule', 'supplierClient', 'planningClients.client.supplierClient']);

        return $planning->supplierVehicule
            ?: $planning->supplierClient
            ?: $planning->planningClients
                ->map(fn ($item) => $item->client?->supplierClient)
                ->filter()
                ->first();
    }

    private function hasValidRecipientEmail(?string $email): bool
    {
        $email = trim((string) $email);

        return $email !== ''
            && !str_ends_with(strtolower($email), '.local')
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function logoDataUri(): ?string
    {
        $path = resource_path('js/assets/images/logo_md_tours.png');

        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }
}
