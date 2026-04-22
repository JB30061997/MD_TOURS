<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\DriverFuelInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DriverFuelInvoiceController extends Controller
{
    /**
     * Affichage liste dyal les factures carburant
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $invoices = DriverFuelInvoice::with(['driver', 'plannings'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('total_amount', 'like', "%{$search}%")
                        ->orWhereHas('driver', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('DriverFuelInvoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Page création
     */
    public function create()
    {
        $drivers = Driver::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('DriverFuelInvoices/Create', [
            'drivers' => $drivers,
        ]);
    }

    /**
     * Jib planningat dyal driver bin période
     */
    public function getDriverPlannings(Request $request)
    {
        $request->validate([
            'driver_id'    => ['required', 'exists:drivers,id'],
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $driverId = $request->driver_id;
        $periodStart = $request->period_start;
        $periodEnd = $request->period_end;

        $plannings = Planning::with(['service'])
            ->where('driver_id', $driverId)
            ->whereDate('date_du', '>=', $periodStart)
            ->whereDate('date_au', '<=', $periodEnd)
            ->orderBy('date_du')
            ->get();

        return response()->json([
            'plannings' => $plannings,
        ]);
    }

    /**
     * Enregistrement facture + fichier PDF + planningat sélectionnés
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id'          => ['required', 'exists:drivers,id'],
            'period_start'       => ['required', 'date'],
            'period_end'         => ['required', 'date', 'after_or_equal:period_start'],
            'invoice_number'     => ['nullable', 'string', 'max:255'],
            'invoice_date'       => ['nullable', 'date'],
            'total_amount'       => ['required', 'numeric', 'min:0'],
            'pdf_file'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes'              => ['nullable', 'string'],
            'planning_ids'       => ['nullable', 'array'],
            'planning_ids.*'     => ['exists:plannings,id'],
        ]);

        DB::transaction(function () use ($request) {
            $pdfPath = null;

            if ($request->hasFile('pdf_file')) {
                $pdfPath = $request->file('pdf_file')->store('driver-fuel-invoices', 'public');
            }

            $invoice = DriverFuelInvoice::create([
                'driver_id'      => $request->driver_id,
                'period_start'   => $request->period_start,
                'period_end'     => $request->period_end,
                'invoice_number' => $request->invoice_number,
                'invoice_date'   => $request->invoice_date,
                'total_amount'   => $request->total_amount,
                'pdf_path'       => $pdfPath,
                'notes'          => $request->notes,
            ]);

            if (!empty($request->planning_ids)) {
                $syncData = [];

                foreach ($request->planning_ids as $planningId) {
                    $syncData[$planningId] = [
                        'is_selected' => true,
                        'notes'       => null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                $invoice->plannings()->sync($syncData);
            }
        });

        return redirect()
            ->route('driver-fuel-invoices.index')
            ->with('success', 'Facture carburant ajoutée avec succès.');
    }

    /**
     * Page modification
     */
    public function edit($id)
    {
        $invoice = DriverFuelInvoice::with('plannings')->findOrFail($id);
        $drivers = Driver::select('id', 'name')->orderBy('name')->get();

        $plannings = Planning::with(['service'])
            ->where('driver_id', $invoice->driver_id)
            ->whereDate('date_du', '>=', $invoice->period_start)
            ->whereDate('date_au', '<=', $invoice->period_end)
            ->orderBy('date_du')
            ->get();

        return Inertia::render('DriverFuelInvoices/Edit', [
            'invoice' => $invoice,
            'drivers' => $drivers,
            'plannings' => $plannings,
            'selectedPlanningIds' => $invoice->plannings->pluck('id')->values(),
        ]);
    }

    /**
     * Update facture + fichier PDF + planningat
     */
    public function update(Request $request, $id)
    {
        $invoice = DriverFuelInvoice::findOrFail($id);

        $request->validate([
            'driver_id'          => ['required', 'exists:drivers,id'],
            'period_start'       => ['required', 'date'],
            'period_end'         => ['required', 'date', 'after_or_equal:period_start'],
            'invoice_number'     => ['nullable', 'string', 'max:255'],
            'invoice_date'       => ['nullable', 'date'],
            'total_amount'       => ['required', 'numeric', 'min:0'],
            'pdf_file'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes'              => ['nullable', 'string'],
            'planning_ids'       => ['nullable', 'array'],
            'planning_ids.*'     => ['exists:plannings,id'],
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $pdfPath = $invoice->pdf_path;

            if ($request->hasFile('pdf_file')) {
                if (!empty($invoice->pdf_path) && Storage::disk('public')->exists($invoice->pdf_path)) {
                    Storage::disk('public')->delete($invoice->pdf_path);
                }

                $pdfPath = $request->file('pdf_file')->store('driver-fuel-invoices', 'public');
            }

            $invoice->update([
                'driver_id'      => $request->driver_id,
                'period_start'   => $request->period_start,
                'period_end'     => $request->period_end,
                'invoice_number' => $request->invoice_number,
                'invoice_date'   => $request->invoice_date,
                'total_amount'   => $request->total_amount,
                'pdf_path'       => $pdfPath,
                'notes'          => $request->notes,
            ]);

            $syncData = [];

            if (!empty($request->planning_ids)) {
                foreach ($request->planning_ids as $planningId) {
                    $syncData[$planningId] = [
                        'is_selected' => true,
                        'notes'       => null,
                        'updated_at'  => now(),
                    ];
                }
            }

            $invoice->plannings()->sync($syncData);
        });

        return redirect()
            ->route('driver-fuel-invoices.index')
            ->with('success', 'Facture carburant modifiée avec succès.');
    }

    /**
     * Supprimer facture + fichier PDF
     */
    public function destroy($id)
    {
        $invoice = DriverFuelInvoice::findOrFail($id);

        if (!empty($invoice->pdf_path) && Storage::disk('public')->exists($invoice->pdf_path)) {
            Storage::disk('public')->delete($invoice->pdf_path);
        }

        $invoice->delete();

        return redirect()
            ->route('driver-fuel-invoices.index')
            ->with('success', 'Facture carburant supprimée avec succès.');
    }

    /**
     * Affichage détail
     */
    public function show($id)
    {
        $invoice = DriverFuelInvoice::with([
            'driver',
            'plannings.service',
        ])->findOrFail($id);

        return Inertia::render('DriverFuelInvoices/Show', [
            'invoice' => $invoice,
            'pdf_url' => $invoice->pdf_path ? Storage::url($invoice->pdf_path) : null,
        ]);
    }
}
