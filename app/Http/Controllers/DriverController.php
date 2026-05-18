<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        Driver::whereNull('user_id')->get()->each(function ($driver) {
            $email = $driver->email ?: Str::slug($driver->name) . '-' . $driver->id . '@md-tours.local';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $driver->name,
                    'password' => Hash::make($email),
                ]
            );

            $role = Role::firstOrCreate([
                'name' => 'driver',
                'guard_name' => 'web',
            ]);

            if (!$user->hasRole('driver')) {
                $user->assignRole($role);
            }

            $driver->update([
                'user_id' => $user->id,
                'email' => $email,
            ]);
        });

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

        DB::transaction(function () use (&$data, &$driver) {

            $email = $data['email'] ?? null;

            if (!$email) {
                $baseEmail = Str::slug($data['name']) . '@md-tours.local';
                $email = $baseEmail;

                $counter = 1;

                while (\App\Models\User::where('email', $email)->exists()) {
                    $email = Str::slug($data['name']) . '-' . $counter . '@md-tours.local';
                    $counter++;
                }
            }

            // Password howa email
            $password = $email;

            // Create user automatique
            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($password),
                ]
            );

            // Assign role driver
            if (method_exists($user, 'assignRole')) {

                $role = Role::firstOrCreate([
                    'name' => 'driver',
                    'guard_name' => 'web',
                ]);

                if (!$user->hasRole('driver')) {
                    $user->assignRole($role);
                }
            }

            // Create driver
            $driver = Driver::create([
                'user_id' => $user->id,
                'name'    => $data['name'],
                'phone'   => $data['phone'] ?? null,
                'email'   => $email,
                'status'  => $data['status'] ?? 'Actif',
                'notes'   => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('drivers.index')->with([
            'success' => 'Driver ajouté avec succès + User créé automatiquement',
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
