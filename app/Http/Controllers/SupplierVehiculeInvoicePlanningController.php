<?php

namespace App\Http\Controllers;

use App\Models\SupplierVehiculeInvoice;
use App\Models\SupplierVehiculeInvoicePlanning;
use Illuminate\Http\Request;

class SupplierVehiculeInvoicePlanningController extends Controller
{
    /**
     * Ajouter planning wa7d l facture fournisseur véhicule
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_vehicule_invoice_id' => ['required', 'exists:supplier_vehicule_invoices,id'],
            'planning_id'                  => ['required', 'exists:plannings,id'],
            'is_selected'                  => ['nullable', 'boolean'],
            'notes'                        => ['nullable', 'string'],
        ]);

        $exists = SupplierVehiculeInvoicePlanning::where('supplier_vehicule_invoice_id', $request->supplier_vehicule_invoice_id)
            ->where('planning_id', $request->planning_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Had planning déjà lié b had facture fournisseur véhicule.');
        }

        SupplierVehiculeInvoicePlanning::create([
            'supplier_vehicule_invoice_id' => $request->supplier_vehicule_invoice_id,
            'planning_id'                  => $request->planning_id,
            'is_selected'                  => $request->boolean('is_selected', true),
            'notes'                        => $request->notes,
        ]);

        return back()->with('success', 'Planning ajouté avec succès.');
    }

    /**
     * Modifier lien bin facture fournisseur véhicule w planning
     */
    public function update(Request $request, $id)
    {
        $link = SupplierVehiculeInvoicePlanning::findOrFail($id);

        $request->validate([
            'is_selected' => ['nullable', 'boolean'],
            'notes'       => ['nullable', 'string'],
        ]);

        $link->update([
            'is_selected' => $request->boolean('is_selected', true),
            'notes'       => $request->notes,
        ]);

        return back()->with('success', 'Lien planning mis à jour avec succès.');
    }

    /**
     * Supprimer lien
     */
    public function destroy($id)
    {
        $link = SupplierVehiculeInvoicePlanning::findOrFail($id);
        $link->delete();

        return back()->with('success', 'Planning retiré de la facture avec succès.');
    }

    /**
     * Jib ga3 planningat liés b facture fournisseur véhicule
     */
    public function byInvoice($invoiceId)
    {
        $invoice = SupplierVehiculeInvoice::with(['plannings.service'])
            ->findOrFail($invoiceId);

        return response()->json([
            'invoice' => $invoice,
            'plannings' => $invoice->plannings,
        ]);
    }
}
