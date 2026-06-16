<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\ReservationDraft;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReservationDraftController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $drafts = ReservationDraft::with(['mailMessage', 'planning'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('ReservationDrafts/Index', [
            'drafts' => $drafts,
            'filters' => [
                'status' => $status,
            ],
            'counts' => [
                'pending' => ReservationDraft::where('status', 'pending')->count(),
                'validated' => ReservationDraft::where('status', 'validated')->count(),
                'rejected' => ReservationDraft::where('status', 'rejected')->count(),
            ],
            'supplierVehicules' => SupplierVehicule::orderBy('name')->get(),
            'supplierClients' => SupplierClient::orderBy('name')->get(),
            'drivers' => Driver::orderBy('name')->get(),
            'guides' => Guide::orderBy('name')->get(),
            'services' => Service::orderBy('designation')->get(),
            'clients' => Client::orderBy('full_name')->get(),
            'destinations' => Destination::orderBy('name')->get(),
            'vehicules' => Vehicule::orderBy('matricule')->get(),
        ]);
    }

    public function validateDraft(Request $request, ReservationDraft $reservationDraft)
    {
        if ($reservationDraft->status === 'validated') {
            return back()->with('error', 'Cette réservation est déjà validée.');
        }

        $data = $request->validate([
            'date_du' => ['required', 'date'],
            'date_au' => ['nullable', 'date'],
            'ref_dossier' => ['required', 'string', 'max:255'],
            'nbr_personnes' => ['nullable', 'integer'],
            'flight' => ['nullable', 'string', 'max:255'],
            'heure' => ['nullable'],
            'point_depart' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'passenger_names' => ['nullable', 'string'],
            'supplier_client_name' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'exists:services,id'],
            'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
            'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'destination_id' => ['nullable', 'exists:destinations,id'],
            'vehicule_id' => ['nullable', 'exists:vehicules,id'],
            'budget' => ['nullable', 'numeric'],
            'supplier_price' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($reservationDraft, $data) {
            $service = null;
            $destination = null;
            $supplierClient = null;

            if (!empty($data['service_id'])) {
                $service = Service::find($data['service_id']);
            } elseif (!empty($data['service_name'])) {
                $service = Service::firstOrCreate(['designation' => trim($data['service_name'])]);
            }

            if (!empty($data['destination_id'])) {
                $destination = Destination::find($data['destination_id']);
            } elseif (!empty($data['destination_name'])) {
                $destination = Destination::firstOrCreate(['name' => trim($data['destination_name'])]);
            }

            if (!empty($data['supplier_client_id'])) {
                $supplierClient = SupplierClient::find($data['supplier_client_id']);
            } elseif (!empty($data['supplier_client_name'])) {
                $supplierClient = SupplierClient::firstOrCreate(['name' => trim($data['supplier_client_name'])]);
            }

            $planning = Planning::create([
                'date_du' => $data['date_du'],
                'date_au' => $data['date_au'] ?? null,
                'ref_dossier' => $data['ref_dossier'],
                'nbr_personnes' => $data['nbr_personnes'] ?? null,
                'flight' => $data['flight'] ?? null,
                'heure' => $data['heure'] ?? null,
                'point_depart' => $data['point_depart'] ?? null,
                'site' => $data['site'] ?? null,
                'service_id' => $service?->id,
                'destination_id' => $destination?->id,
                'supplier_vehicule_id' => $data['supplier_vehicule_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'guide_id' => $data['guide_id'] ?? null,
                'vehicule_id' => $data['vehicule_id'] ?? null,
                'budget' => $data['budget'] ?? null,
                'supplier_price' => $data['supplier_price'] ?? null,
                'notes' => trim(($data['notes'] ?? '') . "\nSource mail draft #{$reservationDraft->id}"),
            ]);

            foreach ($this->passengerNames($data['passenger_names'] ?? '') as $name) {
                $client = Client::firstOrCreate(
                    [
                        'full_name' => $name,
                        'supplier_client_id' => $supplierClient?->id,
                    ],
                    ['notes' => 'Créé depuis agent mailbox.']
                );

                PlanningClient::firstOrCreate([
                    'planning_id' => $planning->id,
                    'client_id' => $client->id,
                ]);
            }

            $reservationDraft->update([
                'status' => 'validated',
                'planning_id' => $planning->id,
                'parsed_payload' => $data,
                'validated_at' => now(),
            ]);
        });

        return back()->with('success', 'Réservation validée et ajoutée au planning réel.');
    }

    public function reject(Request $request, ReservationDraft $reservationDraft)
    {
        $reservationDraft->update([
            'status' => 'rejected',
            'validation_notes' => $request->input('validation_notes') ?: $reservationDraft->validation_notes,
        ]);

        return back()->with('success', 'Réservation ignorée.');
    }

    private function passengerNames(string $value): array
    {
        return collect(preg_split('/[,;\n]+/', $value))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
