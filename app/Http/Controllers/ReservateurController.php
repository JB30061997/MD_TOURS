<?php

namespace App\Http\Controllers;

use App\Models\Reservateur;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservateurController extends Controller
{
    public function index(Request $request)
    {
        $reservateurs = Reservateur::withCount('reservations')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->statut, fn ($query, $statut) => $query->where('statut', $statut))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reservateurs/Index', [
            'reservateurs' => $reservateurs,
            'filters' => [
                'search' => $request->search ?? '',
                'statut' => $request->statut ?? '',
            ],
            'nextReference' => Reservateur::generateReference(),
        ]);
    }

    public function store(Request $request)
    {
        Reservateur::create($this->validated($request));

        return redirect()
            ->route('reservateurs.index')
            ->with('success', 'Réservateur ajouté avec succès.');
    }

    public function update(Request $request, Reservateur $reservateur)
    {
        $reservateur->update($this->validated($request));

        return redirect()
            ->route('reservateurs.index')
            ->with('success', 'Réservateur modifié avec succès.');
    }

    public function toggle(Reservateur $reservateur)
    {
        $reservateur->update([
            'statut' => $reservateur->statut === 'actif' ? 'inactif' : 'actif',
        ]);

        return redirect()
            ->route('reservateurs.index')
            ->with('success', 'Statut du réservateur mis à jour.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', 'in:actif,inactif'],
        ]);
    }
}
