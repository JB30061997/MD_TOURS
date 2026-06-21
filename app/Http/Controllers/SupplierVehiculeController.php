<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\SupplierVehicule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class SupplierVehiculeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $supplierVehicules = SupplierVehicule::with('user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupplierVehicules/Index', [
            'supplierVehicules' => $supplierVehicules,
            'allSupplierVehicules' => SupplierVehicule::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('SupplierVehicules/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use (&$validated) {

            $email = $validated['email'] ?? null;

            if (!$email) {
                $baseEmail = Str::slug($validated['name']) . '@md-tours.local';
                $email = $baseEmail;

                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = Str::slug($validated['name']) . '-' . $counter . '@md-tours.local';
                    $counter++;
                }
            }

            $password = $email;

            if (empty($validated['user_id'])) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $validated['name'],
                        'password' => Hash::make($password),
                    ]
                );

                if (method_exists($user, 'assignRole')) {
                    $role = Role::firstOrCreate([
                        'name' => 'supplier_vehicule',
                        'guard_name' => 'web',
                    ]);

                    if (!$user->hasRole('supplier_vehicule')) {
                        $user->assignRole($role);
                    }
                }

                $validated['user_id'] = $user->id;
            }

            $validated['email'] = $email;

            SupplierVehicule::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
        });

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule créé avec succès. User créé automatiquement.');
    }

    public function edit($id)
    {
        return Inertia::render('SupplierVehicules/Edit', [
            'supplierVehicule' => SupplierVehicule::findOrFail($id),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

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

        $supplierVehicule->update($request->only([
            'user_id',
            'name',
            'phone',
            'email',
            'address',
            'notes',
            'is_active',
        ]));

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule modifié avec succès.');
    }

    public function replaceSelected(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'exists:supplier_vehicules,id',
            'replacement_supplier_vehicule_id' => 'required|exists:supplier_vehicules,id',
        ]);

        DB::transaction(function () use ($request) {
            $wrongSuppliers = SupplierVehicule::whereIn('id', $request->selected_ids)->get();

            foreach ($wrongSuppliers as $wrongSupplier) {
                if ($wrongSupplier->id == $request->replacement_supplier_vehicule_id) {
                    continue;
                }

                $email = $wrongSupplier->email ?: Str::slug($wrongSupplier->name) . '-' . $wrongSupplier->id . '@md-tours.local';

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $wrongSupplier->name,
                        'password' => Hash::make(Str::random(16)),
                    ]
                );

                // Ila role driver ma kaynach, kancreyiwha
                if (method_exists($user, 'assignRole')) {
                    $driverRole = \Spatie\Permission\Models\Role::firstOrCreate([
                        'name' => 'driver',
                        'guard_name' => 'web',
                    ]);

                    if (!$user->hasRole('driver')) {
                        $user->assignRole($driverRole);
                    }
                }

                $driver = Driver::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $wrongSupplier->name,
                        'phone' => $wrongSupplier->phone,
                        'email' => $email,
                        'status' => 'Actif',
                        'notes' => 'Created automatically from Vehicle Supplier #' . $wrongSupplier->id,
                    ]
                );

                Planning::where('supplier_vehicule_id', $wrongSupplier->id)
                    ->update([
                        'supplier_vehicule_id' => $request->replacement_supplier_vehicule_id,
                        'driver_id' => $driver->id,
                    ]);

                $wrongSupplier->delete();
            }
        });

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Remplacement effectué avec succès. Drivers créés, plannings corrigés et anciens Vehicle Suppliers supprimés.');
    }

    public function destroy($id)
    {
        $supplierVehicule = SupplierVehicule::findOrFail($id);
        $supplierVehicule->delete();

        return redirect()
            ->route('supplier-vehicules.index')
            ->with('success', 'Supplier véhicule supprimé avec succès.');
    }
}
