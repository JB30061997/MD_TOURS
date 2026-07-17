<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DestinationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = $request->search;

        $destinations = Destination::query()
            ->withCount('destinationPlannings')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Destinations/Index', [
            'destinations' => $destinations,
            'allDestinations' => Destination::query()
                ->withCount('destinationPlannings')
                ->orderBy('name')
                ->orderBy('city')
                ->get(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return Inertia::render('Destinations/Create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Destination::create([
            'name' => $request->name,
            'city' => $request->city,
            'country' => $request->country ?: 'Maroc',
            'type' => $request->type,
            'status' => $request->status ?: 'Actif',
            'notes' => $request->notes,
        ]);

        return redirect()
            ->route('destinations.index')
            ->with('success', 'Destination créée avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $destination = Destination::findOrFail($id);

        return Inertia::render('Destinations/Show', [
            'destination' => $destination,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $destination = Destination::findOrFail($id);

        return Inertia::render('Destinations/Edit', [
            'destination' => $destination,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $destination->update([
            'name' => $request->name,
            'city' => $request->city,
            'country' => $request->country ?: 'Maroc',
            'type' => $request->type,
            'status' => $request->status ?: 'Actif',
            'notes' => $request->notes,
        ]);

        return redirect()
            ->route('destinations.index')
            ->with('success', 'Destination modifiée avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);

        $message = DeleteProtection::blockingMessage('cette destination', [
            ['table' => 'plannings', 'column' => 'destination_id', 'value' => $destination->id, 'label' => 'planning(s)'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $destination->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('cette destination', $exception));
        }

        return redirect()
            ->route('destinations.index')
            ->with('success', 'Destination supprimée avec succès.');
    }

    public function replaceSelected(Request $request)
    {
        abort_unless($request->user()?->can('destinations.manage') === true, 403);

        $data = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'distinct', 'exists:destinations,id'],
            'replacement_destination_id' => ['required', 'integer', 'exists:destinations,id'],
        ]);

        $replacementId = (int) $data['replacement_destination_id'];
        $duplicateIds = collect($data['selected_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $replacementId)
            ->values();

        if ($duplicateIds->isEmpty()) {
            return back()->withErrors([
                'selected_ids' => 'La destination de remplacement ne peut pas être le seul élément sélectionné.',
            ]);
        }

        $replacement = Destination::findOrFail($replacementId);
        $summary = DB::transaction(function () use ($duplicateIds, $replacementId) {
            $duplicates = Destination::query()->whereIn('id', $duplicateIds)->lockForUpdate()->get();
            $planningCount = DB::table('plannings')->whereIn('destination_id', $duplicates->pluck('id'))->count();

            DB::table('plannings')
                ->whereIn('destination_id', $duplicates->pluck('id'))
                ->update(['destination_id' => $replacementId]);

            $deleted = Destination::query()->whereIn('id', $duplicates->pluck('id'))->delete();

            return ['destinations' => $deleted, 'plannings' => $planningCount];
        });

        return redirect()->route('destinations.index')->with(
            'success',
            sprintf(
                '%d destination(s) remplacée(s) par %s. %d planning(s) mis à jour.',
                $summary['destinations'],
                $replacement->name,
                $summary['plannings']
            )
        );
    }
}
