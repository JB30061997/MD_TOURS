<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SupplierVehiculeInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $invoices = SupplierVehiculeInvoice::with(['supplierVehicule', 'plannings'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('total_amount', 'like', "%{$search}%")
                        ->orWhereHas('supplierVehicule', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupplierVehiculeInvoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        $supplierVehicules = SupplierVehicule::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('SupplierVehiculeInvoices/Create', [
            'supplierVehicules' => $supplierVehicules,
        ]);
    }

    public function getSupplierVehiculePlannings(Request $request)
    {
        $request->validate([
            'supplier_vehicule_id' => ['required', 'exists:supplier_vehicules,id'],
            'period_start'         => ['required', 'date'],
            'period_end'           => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $plannings = Planning::with(['service'])
            ->where('supplier_vehicule_id', $request->supplier_vehicule_id)
            ->whereDate('date_du', '>=', $request->period_start)
            ->whereDate('date_au', '<=', $request->period_end)
            ->orderBy('date_du')
            ->get();

        return response()->json([
            'plannings' => $plannings,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_vehicule_id' => ['required', 'exists:supplier_vehicules,id'],
            'period_start'         => ['required', 'date'],
            'period_end'           => ['required', 'date', 'after_or_equal:period_start'],
            'invoice_number'       => ['nullable', 'string', 'max:255'],
            'invoice_date'         => ['nullable', 'date'],
            'total_amount'         => ['required', 'numeric', 'min:0'],
            'pdf_file'             => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes'                => ['nullable', 'string'],
            'planning_ids'         => ['nullable', 'array'],
            'planning_ids.*'       => ['exists:plannings,id'],
        ]);

        DB::transaction(function () use ($request) {
            $pdfPath = null;

            if ($request->hasFile('pdf_file')) {
                $pdfPath = $request->file('pdf_file')
                    ->store('supplier-vehicule-invoices', 'public');
            }

            $invoice = SupplierVehiculeInvoice::create([
                'supplier_vehicule_id' => $request->supplier_vehicule_id,
                'period_start'         => $request->period_start,
                'period_end'           => $request->period_end,
                'invoice_number'       => $request->invoice_number,
                'invoice_date'         => $request->invoice_date,
                'total_amount'         => $request->total_amount,
                'pdf_path'             => $pdfPath,
                'notes'                => $request->notes,
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
            ->route('supplier-vehicule-invoices.index')
            ->with('success', 'Facture fournisseur véhicule ajoutée avec succès.');
    }

    public function edit($id)
    {
        $invoice = SupplierVehiculeInvoice::with('plannings')->findOrFail($id);

        $supplierVehicules = SupplierVehicule::select('id', 'name')
            ->orderBy('name')
            ->get();

        $plannings = Planning::with(['service'])
            ->where('supplier_vehicule_id', $invoice->supplier_vehicule_id)
            ->whereDate('date_du', '>=', $invoice->period_start)
            ->whereDate('date_au', '<=', $invoice->period_end)
            ->orderBy('date_du')
            ->get();

        return Inertia::render('SupplierVehiculeInvoices/Edit', [
            'invoice' => $invoice,
            'supplierVehicules' => $supplierVehicules,
            'plannings' => $plannings,
            'selectedPlanningIds' => $invoice->plannings->pluck('id')->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $invoice = SupplierVehiculeInvoice::findOrFail($id);

        $request->validate([
            'supplier_vehicule_id' => ['required', 'exists:supplier_vehicules,id'],
            'period_start'         => ['required', 'date'],
            'period_end'           => ['required', 'date', 'after_or_equal:period_start'],
            'invoice_number'       => ['nullable', 'string', 'max:255'],
            'invoice_date'         => ['nullable', 'date'],
            'total_amount'         => ['required', 'numeric', 'min:0'],
            'pdf_file'             => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes'                => ['nullable', 'string'],
            'planning_ids'         => ['nullable', 'array'],
            'planning_ids.*'       => ['exists:plannings,id'],
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $pdfPath = $invoice->pdf_path;

            if ($request->hasFile('pdf_file')) {
                if (!empty($invoice->pdf_path) && Storage::disk('public')->exists($invoice->pdf_path)) {
                    Storage::disk('public')->delete($invoice->pdf_path);
                }

                $pdfPath = $request->file('pdf_file')
                    ->store('supplier-vehicule-invoices', 'public');
            }

            $invoice->update([
                'supplier_vehicule_id' => $request->supplier_vehicule_id,
                'period_start'         => $request->period_start,
                'period_end'           => $request->period_end,
                'invoice_number'       => $request->invoice_number,
                'invoice_date'         => $request->invoice_date,
                'total_amount'         => $request->total_amount,
                'pdf_path'             => $pdfPath,
                'notes'                => $request->notes,
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
            ->route('supplier-vehicule-invoices.index')
            ->with('success', 'Facture fournisseur véhicule modifiée avec succès.');
    }

    public function show($id)
    {
        $invoice = SupplierVehiculeInvoice::with([
            'supplierVehicule',
            'plannings.service',
        ])->findOrFail($id);

        return Inertia::render('SupplierVehiculeInvoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function destroy($id)
    {
        $invoice = SupplierVehiculeInvoice::findOrFail($id);

        DB::transaction(function () use ($invoice) {
            if (!empty($invoice->pdf_path) && Storage::disk('public')->exists($invoice->pdf_path)) {
                Storage::disk('public')->delete($invoice->pdf_path);
            }

            $invoice->plannings()->detach();
            $invoice->delete();
        });

        return redirect()
            ->route('supplier-vehicule-invoices.index')
            ->with('success', 'Facture fournisseur véhicule supprimée avec succès.');
    }
}