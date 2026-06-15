<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\ReservationDraft;
use App\Models\Service;
use App\Models\SupplierClient;
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
            'budget' => ['nullable', 'numeric'],
            'supplier_price' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($reservationDraft, $data) {
            $service = null;
            $destination = null;
            $supplierClient = null;

            if (!empty($data['service_name'])) {
                $service = Service::firstOrCreate(['designation' => trim($data['service_name'])]);
            }

            if (!empty($data['destination_name'])) {
                $destination = Destination::firstOrCreate(['name' => trim($data['destination_name'])]);
            }

            if (!empty($data['supplier_client_name'])) {
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
