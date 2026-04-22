<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Liste des users
     */
    public function index(Request $request)
    {
        $users = User::with(['roles', 'driver', 'guide', 'supplier'])
            ->latest()
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => (int) $user->active,
                    'created_at' => optional($user->created_at)->format('Y-m-d H:i'),

                    // role principal
                    'role' => optional($user->roles->first())->name,

                    // profil lié
                    'linked_profile' =>
                    optional($user->driver)->name
                        ?? optional($user->guide)->name
                        ?? optional($user->supplier)->name
                        ?? '-',
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Form create
     */
    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => [
                'admin',
                'administrateur',
                'driver',
                'supplier',
                'guide',
            ],

            'drivers' => Driver::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),

            'guides' => Guide::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),

            'suppliers' => Supplier::select('id', 'name')
                ->whereNull('user_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store user
     */
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
            'supplier_id' => ['nullable'],
        ]);

        // create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active' => $request->active,
        ]);

        // assign role
        $user->syncRoles([$request->role]);

        /**
         * Liaison profil selon role
         */

        // reset sécurité
        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        Supplier::where('user_id', $user->id)->update(['user_id' => null]);

        if ($request->role === 'driver' && $request->driver_id) {
            Driver::where('id', $request->driver_id)
                ->update(['user_id' => $user->id]);
        }

        if ($request->role === 'guide' && $request->guide_id) {
            Guide::where('id', $request->guide_id)
                ->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier' && $request->supplier_id) {
            Supplier::where('id', $request->supplier_id)
                ->update(['user_id' => $user->id]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Form edit
     */
    public function edit(User $user)
    {
        $user->load(['roles', 'driver', 'guide', 'supplier']);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (int) $user->active,
                'role' => optional($user->roles->first())->name,

                'driver_id' => optional($user->driver)->id,
                'guide_id' => optional($user->guide)->id,
                'supplier_id' => optional($user->supplier)->id,
            ],

            'roles' => [
                'admin',
                'administrateur',
                'driver',
                'supplier',
                'guide',
            ],

            'drivers' => Driver::select('id', 'name')
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $user->id);
                })
                ->orderBy('name')
                ->get(),

            'guides' => Guide::select('id', 'name')
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $user->id);
                })
                ->orderBy('name')
                ->get(),

            'suppliers' => Supplier::select('id', 'name')
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $user->id);
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update user
     */
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
            'supplier_id' => ['nullable'],
        ]);

        // update user
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'active' => $request->active,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // update role
        $user->syncRoles([$request->role]);

        /**
         * reset old relations
         */
        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        Supplier::where('user_id', $user->id)->update(['user_id' => null]);

        /**
         * new relations
         */
        if ($request->role === 'driver' && $request->driver_id) {
            Driver::where('id', $request->driver_id)
                ->update(['user_id' => $user->id]);
        }

        if ($request->role === 'guide' && $request->guide_id) {
            Guide::where('id', $request->guide_id)
                ->update(['user_id' => $user->id]);
        }

        if ($request->role === 'supplier' && $request->supplier_id) {
            Supplier::where('id', $request->supplier_id)
                ->update(['user_id' => $user->id]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // sécurité reset relations
        Driver::where('user_id', $user->id)->update(['user_id' => null]);
        Guide::where('user_id', $user->id)->update(['user_id' => null]);
        Supplier::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Active / Désactive user
     */
    public function toggleStatus(User $user)
    {
        $user->update([
            'active' => !$user->active
        ]);

        return back()->with(
            'success',
            $user->active
                ? 'Utilisateur activé avec succès.'
                : 'Utilisateur désactivé avec succès.'
        );
    }
}
