<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'driver', 'guide', 'supplierClients', 'supplierVehicules'])
            ->latest()
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => (int) $user->active,
                    'created_at' => optional($user->created_at)->format('Y-m-d H:i'),

                    'role' => optional($user->roles->first())->name,

                    'linked_profile' =>
                        optional($user->driver)->name
                        ?? optional($user->guide)->name
                        ?? optional($user->supplierClients->first())->name
                        ?? optional($user->supplierVehicules->first())->name
                        ?? '-',
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => [
                'admin',
                'administrateur',
                'guide',
                'driver',
                'supplier_client',
                'supplier_vehicule',
            ],

            'drivers' => Driver::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),

            'guides' => Guide::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),

            'supplierClients' => SupplierClient::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),

            'supplierVehicules' => SupplierVehicule::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role' => ['required'],
            'active' => ['required', 'boolean'],

            'driver_id' => ['nullable'],
            'guide_id' => ['nullable'],
            'supplier_client_id' => ['nullable'],
            'supplier_vehicule_id' => ['nullable'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active' => $request->active,
        ]);

        $user->syncRoles([$request->role]);

        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierClient::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierVehicule::where('user_id', $user->id)->update(['user_id' => null]);

        if ($request->role === 'driver' && $request->driver_id) {
            Driver::where('id', $request->driver_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'guide' && $request->guide_id) {
            Guide::where('id', $request->guide_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier_client' && $request->supplier_client_id) {
            SupplierClient::where('id', $request->supplier_client_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier_vehicule' && $request->supplier_vehicule_id) {
            SupplierVehicule::where('id', $request->supplier_vehicule_id)->update(['user_id' => $user->id]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'driver', 'guide', 'supplierClients', 'supplierVehicules']);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (int) $user->active,
                'role' => optional($user->roles->first())->name,

                'driver_id' => optional($user->driver)->id,
                'guide_id' => optional($user->guide)->id,
                'supplier_client_id' => optional($user->supplierClients->first())->id,
                'supplier_vehicule_id' => optional($user->supplierVehicules->first())->id,
            ],

            'roles' => [
                'admin',
                'administrateur',
                'guide',
                'driver',
                'supplier_client',
                'supplier_vehicule',
            ],

            'drivers' => Driver::select('id', 'name')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('name')
                ->get(),

            'guides' => Guide::select('id', 'name')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('name')
                ->get(),

            'supplierClients' => SupplierClient::select('id', 'name')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('name')
                ->get(),

            'supplierVehicules' => SupplierVehicule::select('id', 'name')
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => ['nullable', 'min:6'],
            'role' => ['required'],
            'active' => ['required', 'boolean'],

            'driver_id' => ['nullable'],
            'guide_id' => ['nullable'],
            'supplier_client_id' => ['nullable'],
            'supplier_vehicule_id' => ['nullable'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'active' => $request->active,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $user->syncRoles([$request->role]);

        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierClient::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierVehicule::where('user_id', $user->id)->update(['user_id' => null]);

        if ($request->role === 'driver' && $request->driver_id) {
            Driver::where('id', $request->driver_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'guide' && $request->guide_id) {
            Guide::where('id', $request->guide_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier_client' && $request->supplier_client_id) {
            SupplierClient::where('id', $request->supplier_client_id)->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier_vehicule' && $request->supplier_vehicule_id) {
            SupplierVehicule::where('id', $request->supplier_vehicule_id)->update(['user_id' => $user->id]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierClient::where('user_id', $user->id)->update(['user_id' => null]);
        SupplierVehicule::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function toggleStatus(User $user)
    {
        $user->update([
            'active' => !$user->active,
        ]);

        return back()->with(
            'success',
            $user->active
                ? 'Utilisateur activé avec succès.'
                : 'Utilisateur désactivé avec succès.'
        );
    }
}