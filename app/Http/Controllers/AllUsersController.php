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

class AllUsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with([
            'roles',
            'driver',
            'guide',
            'supplierClients',
            'supplierVehicules',
        ])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => (bool) $user->active,
                    'created_at' => optional($user->created_at)->format('Y-m-d H:i'),

                    'role' => optional($user->roles->first())->name,

                    'driver_id' => optional($user->driver)->id,
                    'guide_id' => optional($user->guide)->id,
                    'supplier_client_id' => optional($user->supplierClients->first())->id,
                    'supplier_vehicule_id' => optional($user->supplierVehicules->first())->id,

                    'linked_profile' =>
                    optional($user->driver)->name
                        ?? optional($user->guide)->name
                        ?? optional($user->supplierClients->first())->name
                        ?? optional($user->supplierVehicules->first())->name
                        ?? '-',
                ];
            });

        return Inertia::render('Users/AllUsers', [
            'users' => $users,

            'filters' => [
                'search' => $request->search,
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'administrateur',
                    'guide',
                    'driver',
                    'supplier_client',
                    'supplier_vehicule',
                ]),
            ],

            'active' => ['required', 'boolean'],

            'driver_id' => ['nullable', 'exists:drivers,id'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
            'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
        ]);

        $this->validateProfileByRole($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'active' => $data['active'],
        ]);

        $user->syncRoles([$data['role']]);

        $this->syncLinkedProfile($user, $request);

        return redirect()
            ->route('all-users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => ['nullable', 'string', 'min:6'],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'administrateur',
                    'guide',
                    'driver',
                    'supplier_client',
                    'supplier_vehicule',
                ]),
            ],

            'active' => ['required', 'boolean'],

            'driver_id' => ['nullable', 'exists:drivers,id'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
            'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
        ]);

        $this->validateProfileByRole($request);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'active' => $data['active'],
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $user->update($payload);

        $user->syncRoles([$data['role']]);

        $this->resetLinkedProfiles($user);
        $this->syncLinkedProfile($user, $request);

        return redirect()
            ->route('all-users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with(
                'error',
                'Vous ne pouvez pas supprimer votre propre compte.'
            );
        }

        $this->resetLinkedProfiles($user);

        $user->delete();

        return redirect()
            ->route('all-users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function toggleStatus(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with(
                'error',
                'Vous ne pouvez pas désactiver votre propre compte.'
            );
        }

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

    private function validateProfileByRole(Request $request): void
    {
        if ($request->role === 'driver') {
            $request->validate([
                'driver_id' => ['required', 'exists:drivers,id'],
            ]);
        }

        if ($request->role === 'guide') {
            $request->validate([
                'guide_id' => ['required', 'exists:guides,id'],
            ]);
        }

        if ($request->role === 'supplier_client') {
            $request->validate([
                'supplier_client_id' => ['required', 'exists:supplier_clients,id'],
            ]);
        }

        if ($request->role === 'supplier_vehicule') {
            $request->validate([
                'supplier_vehicule_id' => ['required', 'exists:supplier_vehicules,id'],
            ]);
        }
    }

    private function resetLinkedProfiles(User $user): void
    {
        Driver::where('user_id', $user->id)->update([
            'user_id' => null,
        ]);

        Guide::where('user_id', $user->id)->update([
            'user_id' => null,
        ]);

        SupplierClient::where('user_id', $user->id)->update([
            'user_id' => null,
        ]);

        SupplierVehicule::where('user_id', $user->id)->update([
            'user_id' => null,
        ]);
    }

    private function syncLinkedProfile(User $user, Request $request): void
    {
        if ($request->role === 'driver' && $request->driver_id) {
            Driver::where('id', $request->driver_id)->update([
                'user_id' => $user->id,
            ]);
        }

        if ($request->role === 'guide' && $request->guide_id) {
            Guide::where('id', $request->guide_id)->update([
                'user_id' => $user->id,
            ]);
        }

        if ($request->role === 'supplier_client' && $request->supplier_client_id) {
            SupplierClient::where('id', $request->supplier_client_id)->update([
                'user_id' => $user->id,
            ]);
        }

        if ($request->role === 'supplier_vehicule' && $request->supplier_vehicule_id) {
            SupplierVehicule::where('id', $request->supplier_vehicule_id)->update([
                'user_id' => $user->id,
            ]);
        }
    }

    public function show(User $user)
    {
        return redirect()->route('all-users.index');
    }
}
