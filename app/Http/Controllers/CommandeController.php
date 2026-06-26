<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommandeRequest;
use App\Mail\CommandePdfMail;
use App\Models\Commande;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Vehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $supplierId = $request->get('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $commandes = Commande::with(['supplier', 'service', 'driver', 'vehicule', 'guide'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('voucher_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('passenger', 'like', "%{$search}%")
                        ->orWhere('start_point', 'like', "%{$search}%")
                        ->orWhere('end_point', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Commandes/Index', [
            'commandes' => $commandes,
            'filters' => [
                'search' => $search,
                'supplier_id' => $supplierId ?: '',
                'date_from' => $dateFrom ?: '',
                'date_to' => $dateTo ?: '',
            ],
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'drivers' => Driver::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'vehicules' => Vehicule::orderBy('matricule')->get(['id', 'matricule', 'marque', 'modele']),
            'guides' => Guide::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'services' => Service::orderBy('designation')->get(['id', 'designation']),
            'nextVoucherNumber' => $this->nextVoucherNumber(),
        ]);
    }

    public function store(CommandeRequest $request)
    {
        Commande::create($request->validated());

        return redirect()
            ->route('commandes.index')
            ->with('success', 'Commande ajoutée avec succès.');
    }

    public function update(CommandeRequest $request, Commande $commande)
    {
        $commande->update($request->validated());

        return redirect()
            ->route('commandes.index')
            ->with('success', 'Commande modifiée avec succès.');
    }

    public function destroy(Commande $commande)
    {
        $commande->delete();

        return redirect()
            ->route('commandes.index')
            ->with('success', 'Commande supprimée avec succès.');
    }

    public function pdf(Commande $commande)
    {
        $commande->load(['supplier', 'service', 'driver', 'vehicule', 'guide']);
        $pdf = $this->buildPdf($commande);

        return $pdf->download($this->pdfFileName($commande));
    }

    public function sendEmail(Commande $commande)
    {
        $commande->load(['supplier', 'service', 'driver', 'vehicule', 'guide']);

        if (!$commande->supplier?->email) {
            return back()->with('error', 'Le supplier lié à cette commande n’a pas d’adresse email.');
        }

        $pdfContent = $this->buildPdf($commande)->output();

        Mail::to($commande->supplier->email)->send(
            new CommandePdfMail($commande, $pdfContent, $this->pdfFileName($commande))
        );

        return back()->with('success', 'Bon de commande envoyé au supplier avec succès.');
    }

    private function buildPdf(Commande $commande)
    {
        return Pdf::loadView('pdf.commande', [
            'commande' => $commande,
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');
    }

    private function pdfFileName(Commande $commande): string
    {
        return 'bon-de-commande-' . Str::slug($commande->voucher_number) . '.pdf';
    }

    private function nextVoucherNumber(): string
    {
        $nextId = ((int) Commande::max('id')) + 1;

        return 'BC-' . now()->format('Y') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
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
