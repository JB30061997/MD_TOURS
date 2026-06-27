<?php

namespace App\Http\Controllers;

use App\Mail\RoadSheetPdfMail;
use App\Models\Planning;
use App\Models\RoadSheet;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RoadSheetController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $plannings = Planning::with([
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
                        ->orWhereHas('guide', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplierVehicule', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('planningClients.client', fn ($sub) => $sub->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('RoadSheets/Index', [
            'plannings' => $plannings,
            'filters' => [
                'search' => $search,
            ],
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

        if (!$recipient?->email) {
            return back()->with('error', 'Le fournisseur lié à ce planning n’a pas d’adresse email.');
        }

        Mail::to($recipient->email)->send(
            new RoadSheetPdfMail(
                $roadSheet,
                $this->buildPdf($roadSheet)->output(),
                $this->pdfFileName($roadSheet)
            )
        );

        return back()->with('success', 'Roadsheet envoyée au fournisseur avec succès.');
    }

    public function update(Request $request, RoadSheet $roadSheet)
    {
        $data = $request->validate([
            'voucher_number' => ['nullable', 'string', 'max:255'],
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
            $roadSheet->update([
                'voucher_number' => $data['voucher_number'] ?? null,
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

            foreach (($data['lines'] ?? []) as $index => $line) {
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

    private function logoDataUri(): ?string
    {
        $path = resource_path('js/assets/images/logo_md_tours.png');

        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }
}
