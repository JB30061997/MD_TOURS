<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommandeRequest;
use App\Mail\CommandePdfMail;
use App\Models\Commande;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\Vehicule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $focusCommandeId = $request->get('commande_id');

        $commandes = Commande::with(['supplierVehicule', 'supplierClient', 'service', 'driver', 'vehicule', 'guide'])
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
            'supplierClients' => SupplierClient::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'drivers' => Driver::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'vehicules' => Vehicule::orderBy('matricule')->get(['id', 'matricule', 'marque', 'modele']),
            'guides' => Guide::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'services' => Service::orderBy('designation')->get(['id', 'designation']),
            'nextVoucherNumber' => $this->nextVoucherNumber(),
            'focusCommande' => $focusCommandeId
                ? Commande::with(['supplierVehicule', 'supplierClient', 'service', 'driver', 'vehicule', 'guide'])->find($focusCommandeId)
                : null,
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
        $commande->load(['supplierVehicule', 'supplierClient', 'service', 'driver', 'vehicule', 'guide']);
        $pdf = $this->buildPdf($commande);

        return $pdf->stream($this->pdfFileName($commande));
    }

    public function sendEmail(Commande $commande)
    {
        $commande->load(['supplierVehicule', 'supplierClient', 'service', 'driver', 'vehicule', 'guide']);
        $recipient = $this->commandeRecipient($commande);

        if (!$this->hasValidRecipientEmail($recipient?->email)) {
            return back()->with('error', 'Aucun fournisseur véhicule valide avec une adresse email n’est affecté à cette commande.');
        }

        try {
            $pdfContent = $this->buildPdf($commande)->output();

            Mail::to($recipient->email)->send(
                new CommandePdfMail($commande, $pdfContent, $this->pdfFileName($commande))
            );
        } catch (\Throwable $e) {
            Log::warning('Commande email send failed', [
                'commande_id' => $commande->id,
                'recipient' => $recipient->email,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Le bon de commande n’a pas pu être envoyé. Vérifiez l’adresse email du fournisseur ou la configuration SMTP.');
        }

        return back()->with('success', 'Bon de commande envoyé au fournisseur avec succès.');
    }

    public function openFromPlanning(Planning $planning)
    {
        $commande = $this->commandeFromPlanning($planning);

        return redirect()
            ->route('commandes.index', ['commande_id' => $commande->id])
            ->with('success', 'Bon de commande prêt depuis le planning.');
    }

    public function pdfFromPlanning(Planning $planning)
    {
        $commande = $this->commandeFromPlanning($planning);

        return $this->pdf($commande);
    }

    public function sendEmailFromPlanning(Planning $planning)
    {
        $commande = $this->commandeFromPlanning($planning);

        return $this->sendEmail($commande);
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

    private function commandeFromPlanning(Planning $planning): Commande
    {
        $this->ensureCommandesTableExists();

        $planning->load([
            'supplierClient',
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client.supplierClient',
        ]);

        $payload = $this->payloadFromPlanning($planning);
        $commande = Commande::firstOrNew(['planning_id' => $planning->id]);

        if (!$commande->exists) {
            $commande->voucher_number = $this->nextVoucherNumber();
        }

        $commande->fill($payload);
        $commande->save();

        return $commande->fresh(['supplierVehicule', 'supplierClient', 'service', 'driver', 'vehicule', 'guide']);
    }

    private function payloadFromPlanning(Planning $planning): array
    {
        $destinationName = $planning->destination?->name;
        $destinationCity = $planning->destination?->city ?: $destinationName;
        $passengers = $planning->planningClients
            ->map(fn ($item) => $item->client?->full_name)
            ->filter()
            ->implode(', ');

        if (!$passengers) {
            $passengers = $planning->supplierClient?->name ?: null;
        }

        $payload = [
            'planning_id' => $planning->id,
            'supplier_vehicule_id' => $planning->supplier_vehicule_id,
            'supplier_client_id' => $this->supplierClientIdFromPlanning($planning),
            'start_date' => $planning->date_du?->toDateString(),
            'end_date' => $planning->date_au?->toDateString(),
            'service_id' => $planning->service_id,
            'supplier_price' => $planning->supplier_price,
            'start_point' => $planning->point_depart,
            'start_point_flight' => $planning->flight,
            'start_point_city' => $planning->point_depart ?: $planning->site,
            'start_point_time' => $planning->heure?->format('H:i'),
            'end_point' => $destinationName,
            'end_point_flight' => $planning->flight,
            'end_point_city' => $destinationCity,
            'end_point_time' => $planning->heure?->format('H:i'),
            'driver_id' => $planning->driver_id,
            'vehicule_id' => $planning->vehicule_id,
            'guide_id' => $planning->guide_id,
            'passenger' => $passengers,
            'number_pax' => $planning->nbr_personnes,
            'reference' => $planning->ref_dossier,
            'date' => $planning->date_du?->toDateString(),
            'signature' => 'MD Tours',
        ];

        if (Schema::hasColumn('commandes', 'supplier_id')) {
            $payload['supplier_id'] = $planning->supplier_vehicule_id;
        }

        return $payload;
    }

    private function supplierClientIdFromPlanning(Planning $planning): ?int
    {
        return $planning->supplier_client_id
            ?: $planning->planningClients
                ->map(fn ($item) => $item->client?->supplier_client_id)
                ->filter()
                ->first();
    }

    private function commandeRecipient(Commande $commande): ?SupplierVehicule
    {
        return $commande->supplierVehicule;
    }

    private function hasValidRecipientEmail(?string $email): bool
    {
        $email = trim((string) $email);

        return $email !== ''
            && !str_ends_with(strtolower($email), '.local')
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function ensureCommandesTableExists(): void
    {
        if (!Schema::hasTable('commandes')) {
            Schema::create('commandes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('planning_id')->nullable()->constrained('plannings')->nullOnDelete();
                $table->foreignId('supplier_vehicule_id')->nullable()->constrained('supplier_vehicules')->nullOnDelete();
                $table->foreignId('supplier_client_id')->nullable()->constrained('supplier_clients')->nullOnDelete();
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

        if (!Schema::hasColumn('commandes', 'planning_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->foreignId('planning_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('plannings')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('commandes', 'supplier_client_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->foreignId('supplier_client_id')
                    ->nullable()
                    ->after('supplier_vehicule_id')
                    ->constrained('supplier_clients')
                    ->nullOnDelete();
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('commandes', 'supplier_vehicule_id')) {
            DB::statement('ALTER TABLE commandes MODIFY supplier_vehicule_id BIGINT UNSIGNED NULL');
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('commandes', 'supplier_id')) {
            DB::statement('ALTER TABLE commandes MODIFY supplier_id BIGINT UNSIGNED NULL');
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
