<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommandeRequest;
use App\Mail\CommandePdfMail;
use App\Models\Commande;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Models\Vehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCommandesTableExists();

        $search = trim((string) $request->get('search', ''));
        $supplierVehiculeId = $request->get('supplier_vehicule_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $commandes = Commande::with(['supplierVehicule', 'service', 'driver', 'vehicule', 'guide'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('voucher_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('passenger', 'like', "%{$search}%")
                        ->orWhere('start_point', 'like', "%{$search}%")
                        ->orWhere('end_point', 'like', "%{$search}%")
                        ->orWhereHas('supplierVehicule', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($supplierVehiculeId, fn ($query) => $query->where('supplier_vehicule_id', $supplierVehiculeId))
            ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Commandes/Index', [
            'commandes' => $commandes,
            'filters' => [
                'search' => $search,
                'supplier_vehicule_id' => $supplierVehiculeId ?: '',
                'date_from' => $dateFrom ?: '',
                'date_to' => $dateTo ?: '',
            ],
            'supplierVehicules' => SupplierVehicule::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'drivers' => Driver::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'vehicules' => Vehicule::orderBy('matricule')->get(['id', 'matricule', 'marque', 'modele']),
            'guides' => Guide::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'services' => Service::orderBy('designation')->get(['id', 'designation']),
            'nextVoucherNumber' => $this->nextVoucherNumber(),
        ]);
    }

    public function store(CommandeRequest $request)
    {
        $this->ensureCommandesTableExists();

        Commande::create($this->commandePayload($request));

        return redirect()
            ->route('commandes.index')
            ->with('success', 'Commande ajoutée avec succès.');
    }

    public function update(CommandeRequest $request, Commande $commande)
    {
        $commande->update($this->commandePayload($request));

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
        $commande->load(['supplierVehicule', 'service', 'driver', 'vehicule', 'guide']);
        $pdf = $this->buildPdf($commande);

        return $pdf->download($this->pdfFileName($commande));
    }

    public function sendEmail(Commande $commande)
    {
        $commande->load(['supplierVehicule', 'service', 'driver', 'vehicule', 'guide']);

        if (!$commande->supplierVehicule?->email) {
            return back()->with('error', 'Le supplier lié à cette commande n’a pas d’adresse email.');
        }

        $pdfContent = $this->buildPdf($commande)->output();

        Mail::to($commande->supplierVehicule->email)->send(
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

    private function commandePayload(CommandeRequest $request): array
    {
        $data = $request->validated();

        if (Schema::hasColumn('commandes', 'supplier_id')) {
            $data['supplier_id'] = $data['supplier_vehicule_id'];
        }

        return $data;
    }

    private function ensureCommandesTableExists(): void
    {
        if (!Schema::hasTable('commandes')) {
            Schema::create('commandes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_vehicule_id')->constrained('supplier_vehicules')->cascadeOnDelete();
                $table->string('voucher_number')->unique();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
                $table->decimal('supplier_price', 12, 2)->nullable();
                $table->string('start_point')->nullable();
                $table->string('start_point_flight')->nullable();
                $table->string('start_point_city')->nullable();
                $table->time('start_point_time')->nullable();
                $table->string('end_point')->nullable();
                $table->string('end_point_flight')->nullable();
                $table->string('end_point_city')->nullable();
                $table->time('end_point_time')->nullable();
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();
                $table->foreignId('guide_id')->nullable()->constrained('guides')->nullOnDelete();
                $table->string('passenger')->nullable();
                $table->unsignedInteger('number_pax')->nullable();
                $table->string('reference')->nullable();
                $table->date('date')->nullable();
                $table->string('signature')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (!Schema::hasColumn('commandes', 'supplier_vehicule_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->foreignId('supplier_vehicule_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('supplier_vehicules')
                    ->nullOnDelete();
            });
        }
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
