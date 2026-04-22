<?php

namespace App\Http\Controllers;

use App\Models\DriverFuelInvoice;
use App\Models\DriverFuelInvoicePlanning;
use Illuminate\Http\Request;

class DriverFuelInvoicePlanningController extends Controller
{
    /**
     * Ajouter planning wa7d l facture mou3ayana
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_fuel_invoice_id' => ['required', 'exists:driver_fuel_invoices,id'],
            'planning_id'            => ['required', 'exists:plannings,id'],
            'is_selected'            => ['nullable', 'boolean'],
            'notes'                  => ['nullable', 'string'],
        ]);

        $exists = DriverFuelInvoicePlanning::where('driver_fuel_invoice_id', $request->driver_fuel_invoice_id)
            ->where('planning_id', $request->planning_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Had planning déjà lié b had facture.');
        }

        DriverFuelInvoicePlanning::create([
            'driver_fuel_invoice_id' => $request->driver_fuel_invoice_id,
            'planning_id'            => $request->planning_id,
            'is_selected'            => $request->boolean('is_selected', true),
            'notes'                  => $request->notes,
        ]);

        return back()->with('success', 'Planning ajouté avec succès.');
    }

    /**
     * Modifier lien wa7d bin facture w planning
     */
    public function update(Request $request, $id)
    {
        $link = DriverFuelInvoicePlanning::findOrFail($id);

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
        $link = DriverFuelInvoicePlanning::findOrFail($id);
        $link->delete();

        return back()->with('success', 'Planning retiré de la facture avec succès.');
    }

    /**
     * Jib ga3 planningat liés b facture
     */
    public function byInvoice($invoiceId)
    {
        $invoice = DriverFuelInvoice::with(['plannings.service'])->findOrFail($invoiceId);

        return response()->json([
            'invoice' => $invoice,
            'plannings' => $invoice->plannings,
        ]);
    }
}