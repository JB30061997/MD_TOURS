<?php

namespace App\Http\Controllers;

use App\Mail\ReservateurReservationCreatedMail;
use App\Models\Reservateur;
use App\Models\ReservateurReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ReservateurPortalController extends Controller
{
    public function login()
    {
        if ($this->currentReservateur()) {
            return redirect()->route('reservateur.portal.index');
        }

        return Inertia::render('ReservateurPortal/Login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
        ]);

        $reservateur = Reservateur::where('reference', strtoupper(trim($data['reference'])))->first();

        if (!$reservateur) {
            return back()->with('error', 'Référence invalide.');
        }

        if (!$reservateur->isActive()) {
            return back()->with('error', 'Ce réservateur est actuellement inactif. Merci de contacter l’administration.');
        }

        session(['reservateur_id' => $reservateur->id]);

        return redirect()->route('reservateur.portal.index');
    }

    public function index()
    {
        if (!$reservateur = $this->currentActiveReservateur()) {
            return redirect()->route('reservateur.login');
        }

        return Inertia::render('ReservateurPortal/Index', [
            'reservateur' => $reservateur,
            'reservations' => $reservateur->reservations()
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        if (!$reservateur = $this->currentActiveReservateur()) {
            return redirect()->route('reservateur.login');
        }

        $reservation = $reservateur->reservations()->create(array_merge(
            $this->validatedReservation($request),
            ['statut' => 'en_attente']
        ));

        $this->notifyAdminAboutReservation($reservation);

        return redirect()
            ->route('reservateur.portal.index')
            ->with('success', 'Réservation créée avec succès.');
    }

    public function update(Request $request, ReservateurReservation $reservation)
    {
        if (!$reservateur = $this->currentActiveReservateur()) {
            return redirect()->route('reservateur.login');
        }

        abort_unless($reservation->reservateur_id === $reservateur->id, 404);

        if (!$reservation->canBeChanged()) {
            return back()->with('error', 'Cette réservation est confirmée et ne peut plus être modifiée.');
        }

        $reservation->update($this->validatedReservation($request));

        return redirect()
            ->route('reservateur.portal.index')
            ->with('success', 'Réservation modifiée avec succès.');
    }

    public function cancel(ReservateurReservation $reservation)
    {
        if (!$reservateur = $this->currentActiveReservateur()) {
            return redirect()->route('reservateur.login');
        }

        abort_unless($reservation->reservateur_id === $reservateur->id, 404);

        if (!$reservation->canBeChanged()) {
            return back()->with('error', 'Cette réservation est confirmée et ne peut plus être annulée.');
        }

        $reservation->update(['statut' => 'annulee']);

        return back()->with('success', 'Réservation annulée avec succès.');
    }

    public function logout()
    {
        session()->forget('reservateur_id');

        return redirect()->route('reservateur.login');
    }

    private function currentReservateur(): ?Reservateur
    {
        $id = session('reservateur_id');

        return $id ? Reservateur::find($id) : null;
    }

    private function currentActiveReservateur(): ?Reservateur
    {
        $reservateur = $this->currentReservateur();

        if (!$reservateur || !$reservateur->isActive()) {
            session()->forget('reservateur_id');
            return null;
        }

        return $reservateur;
    }

    private function validatedReservation(Request $request): array
    {
        return $request->validate([
            'service' => ['required', 'string', 'max:255'],
            'lieu_depart' => ['required', 'string', 'max:255'],
            'lieu_arrivee' => ['required', 'string', 'max:255'],
            'date_service' => ['required', 'date'],
            'heure_souhaitee' => ['nullable'],
            'nombre_personnes' => ['nullable', 'integer', 'min:1'],
            'contact' => ['nullable', 'string', 'max:255'],
            'informations_complementaires' => ['nullable', 'string'],
        ]);
    }

    private function notifyAdminAboutReservation(ReservateurReservation $reservation): void
    {
        try {
            Mail::to('zegouti@md-tours.com')
                ->send(new ReservateurReservationCreatedMail($reservation));
        } catch (\Throwable $exception) {
            Log::error('Reservateur reservation notification email failed', [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
