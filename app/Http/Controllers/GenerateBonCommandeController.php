<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\SupplierVehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GenerateBonCommandeController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->input('supplier_vehicule_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $rows = collect();
        $selectedSupplier = null;

        if ($supplierId && $dateFrom && $dateTo) {
            $selectedSupplier = SupplierVehicule::find($supplierId);
            $rows = $this->planningQuery($supplierId, $dateFrom, $dateTo)
                ->get()
                ->map(fn (Planning $planning) => $this->formatPlanningRow($planning));
        }

        return Inertia::render('GenerateBonCMD/Index', [
            'supplierVehicules' => SupplierVehicule::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'filters' => [
                'supplier_vehicule_id' => $supplierId ?: '',
                'date_from' => $dateFrom ?: now()->startOfMonth()->toDateString(),
                'date_to' => $dateTo ?: now()->endOfMonth()->toDateString(),
            ],
            'selectedSupplier' => $selectedSupplier,
            'rows' => $rows->values(),
            'totals' => $this->totals($rows),
        ]);
    }

    public function pdf(Request $request)
    {
        $data = $request->validate([
            'planning_ids' => ['required', 'array', 'min:1'],
            'planning_ids.*' => ['integer', 'exists:plannings,id'],
            'supplier_vehicule_id' => ['nullable', 'integer', 'exists:supplier_vehicules,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = Planning::with($this->relations())
            ->whereIn('id', $data['planning_ids']);

        if (!empty($data['supplier_vehicule_id'])) {
            $query->where('supplier_vehicule_id', $data['supplier_vehicule_id']);
        }

        $plannings = $query
            ->orderBy('date_du')
            ->orderBy('heure')
            ->orderBy('ref_dossier')
            ->get();

        if ($plannings->isEmpty()) {
            return back()->with('error', 'Aucun planning sélectionné pour générer le bon de commande.');
        }

        $rows = $plannings->map(fn (Planning $planning) => $this->formatPlanningRow($planning));
        $supplier = $plannings->first()?->supplierVehicule;

        $pdf = Pdf::loadView('pdf.generate-boncmd', [
            'rows' => $rows,
            'supplier' => $supplier,
            'dateFrom' => $data['date_from'] ?? $plannings->min('date_du')?->toDateString(),
            'dateTo' => $data['date_to'] ?? $plannings->max('date_du')?->toDateString(),
            'totals' => $this->totals($rows),
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $name = 'boncmd-' . str($supplier?->name ?: 'supplier')->slug() . '-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->stream($name);
    }

    private function planningQuery($supplierId, string $dateFrom, string $dateTo)
    {
        return Planning::with($this->relations())
            ->where('supplier_vehicule_id', $supplierId)
            ->whereDate('date_du', '>=', $dateFrom)
            ->whereDate('date_du', '<=', $dateTo)
            ->orderBy('date_du')
            ->orderBy('heure')
            ->orderBy('ref_dossier');
    }

    private function relations(): array
    {
        return [
            'supplierVehicule',
            'service',
            'vehicule',
            'destination',
            'planningClients.client',
        ];
    }

    private function formatPlanningRow(Planning $planning): array
    {
        $clients = $planning->planningClients
            ->map(fn ($item) => $item->client?->full_name)
            ->filter()
            ->values()
            ->all();

        $serviceName = $planning->service?->designation ?: '-';
        $route = collect([$planning->point_depart, $planning->destination?->name ?: $planning->site])
            ->filter()
            ->implode(' // ');
        $serviceLabel = trim($serviceName . ($route ? ' - ' . $route : ''));
        $supplierPrice = (float) ($planning->supplier_price ?? 0);

        return [
            'id' => $planning->id,
            'du' => $planning->date_du?->format('d/m/Y') ?: '-',
            'date_sort' => $planning->date_du?->toDateString(),
            'au' => trim(collect([$planning->ref_dossier, implode(', ', $clients)])->filter()->implode(' - ')) ?: '-',
            'reference' => $planning->ref_dossier ?: '-',
            'clients' => $clients,
            'bus' => ($planning->vehicule?->nombre_places ? str_pad((string) $planning->vehicule->nombre_places, 2, '0', STR_PAD_LEFT) : '-') . ' Places',
            'vehicle' => trim(collect([$planning->vehicule?->matricule, $planning->vehicule?->marque, $planning->vehicule?->modele])->filter()->implode(' ')) ?: '-',
            'service' => $serviceLabel ?: '-',
            'unit_price' => $supplierPrice,
            'total_price' => $supplierPrice,
        ];
    }

    private function totals($rows): array
    {
        $collection = collect($rows);

        return [
            'count' => $collection->count(),
            'unit_price' => round($collection->sum('unit_price'), 2),
            'total_price' => round($collection->sum('total_price'), 2),
        ];
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
