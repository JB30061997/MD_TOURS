<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;
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

        $destination->delete();

        return redirect()
            ->route('destinations.index')
            ->with('success', 'Destination supprimée avec succès.');
    }
}
