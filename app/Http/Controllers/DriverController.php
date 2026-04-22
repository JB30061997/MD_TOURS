<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $drivers = Driver::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Drivers/Index', [
            'drivers' => $drivers,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Drivers/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:255'],
            'email'  => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes'  => ['nullable', 'string'],
        ]);

        $driver = Driver::create($data);

        return redirect()->route('drivers.index')->with([
            'success' => 'Driver ajouté avec succès',
            'lastCreatedDriver' => $driver
        ]);
    }

    public function edit($id)
    {
        return Inertia::render('Drivers/Edit', [
            'driver' => Driver::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:255'],
            'email'  => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes'  => ['nullable', 'string'],
        ]);

        Driver::findOrFail($id)->update($data);

        return redirect()->route('drivers.index')
            ->with('success', 'Driver modifié avec succès');
    }

    public function destroy($id)
    {
        Driver::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Driver supprimé avec succès');
    }
}
