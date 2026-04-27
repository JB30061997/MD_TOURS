<?php

namespace App\Http\Controllers;

use App\Models\SupplierVehicule;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierVehiculeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = $request->search;

        $supplierVehicules = SupplierVehicule::with('user')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupplierVehicules/Index', [
            'supplierVehicules' => $supplierVehicules,
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
        return Inertia::render('SupplierVehicules/Create', [
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        SupplierVehicule::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'notes' => $request->notes,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule créé avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        return Inertia::render('SupplierVehicules/Edit', [
            'supplierVehicule' => SupplierVehicule::findOrFail($id),
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $supplierVehicule = SupplierVehicule::findOrFail($id);

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $supplierVehicule->update([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'notes' => $request->notes,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule modifié avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $supplierVehicule = SupplierVehicule::findOrFail($id);

        $supplierVehicule->delete();

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule supprimé avec succès.');
    }
}