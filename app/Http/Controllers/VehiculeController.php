<?php

namespace App\Http\Controllers;

use App\Models\Vehicule;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VehiculeController extends Controller
{
    public function index(Request $request)
    {
        $vehicules = Vehicule::query()
            ->with('currentDriverAssignment.driver:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('matricule', 'like', "%{$search}%")
                        ->orWhere('marque', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('currentDriverAssignment.driver', fn ($driverQuery) =>
                            $driverQuery->where('name', 'like', "%{$search}%")
                        );
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vehicules/Index', [
            'vehicules' => $vehicules,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Vehicules/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:255', 'unique:vehicules,matricule'],
            'marque' => ['nullable', 'string', 'max:255'],
            'modele' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'couleur' => ['nullable', 'string', 'max:255'],
            'annee' => ['nullable', 'digits:4'],
            'nombre_places' => ['nullable', 'integer', 'min:1'],
            'carburant' => ['nullable', 'string', 'max:255'],
            'boite_vitesse' => ['nullable', 'string', 'max:255'],
            'numero_assurance' => ['nullable', 'string', 'max:255'],
            'date_expiration_assurance' => ['nullable', 'date'],
            'date_visite_technique' => ['nullable', 'date'],
            'date_expiration_visite' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Vehicule::create($data);

        return redirect()
            ->route('vehicules.index')
            ->with('success', 'Véhicule créé avec succès.');
    }

    public function edit(Vehicule $vehicule)
    {
        return Inertia::render('Vehicules/Edit', [
            'vehicule' => $vehicule,
        ]);
    }

    public function update(Request $request, Vehicule $vehicule)
    {
        $data = $request->validate([
            'matricule' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicules', 'matricule')->ignore($vehicule->id),
            ],
            'marque' => ['nullable', 'string', 'max:255'],
            'modele' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'couleur' => ['nullable', 'string', 'max:255'],
            'annee' => ['nullable', 'digits:4'],
            'nombre_places' => ['nullable', 'integer', 'min:1'],
            'carburant' => ['nullable', 'string', 'max:255'],
            'boite_vitesse' => ['nullable', 'string', 'max:255'],
            'numero_assurance' => ['nullable', 'string', 'max:255'],
            'date_expiration_assurance' => ['nullable', 'date'],
            'date_visite_technique' => ['nullable', 'date'],
            'date_expiration_visite' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $vehicule->update($data);

        return redirect()
            ->route('vehicules.index')
            ->with('success', 'Véhicule modifié avec succès.');
    }

    public function destroy(Vehicule $vehicule)
    {
        $message = DeleteProtection::blockingMessage('ce véhicule', [
            ['table' => 'plannings', 'column' => 'vehicule_id', 'value' => $vehicule->id, 'label' => 'planning(s)'],
            ['table' => 'commandes', 'column' => 'vehicule_id', 'value' => $vehicule->id, 'label' => 'bon(s) de commande'],
            ['table' => 'vehicle_maintenances', 'column' => 'vehicule_id', 'value' => $vehicule->id, 'label' => 'rapport(s) maintenance'],
            ['table' => 'driver_vehicle_assignments', 'column' => 'vehicule_id', 'value' => $vehicule->id, 'label' => 'affectation(s) driver'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $vehicule->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ce véhicule', $exception));
        }

        return redirect()
            ->route('vehicules.index')
            ->with('success', 'Véhicule supprimé avec succès.');
    }
}
