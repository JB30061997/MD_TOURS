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
            'allDrivers' => Driver::query()
                ->withCount('plannings')
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'user_id']),
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

        if ($this->findDuplicateDriver($data['name'])) {
            return back()
                ->withErrors(['name' => 'Ce driver existe déjà dans la liste.'])
                ->withInput();
        }

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

        if ($this->findDuplicateDriver($data['name'], (int) $id)) {
            return back()
                ->withErrors(['name' => 'Ce driver existe déjà dans la liste.'])
                ->withInput();
        }

        $driver = Driver::findOrFail($id);
        $driver->update($data);

        if ($driver->user) {
            $driver->user->update([
                'name' => $data['name'],
                'email' => $data['email'] ?: $driver->user->email,
            ]);
        }

        return redirect()->route('drivers.index')
            ->with('success', 'Driver modifié avec succès');
    }

    public function destroy($id)
    {
        Driver::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Driver supprimé avec succès');
    }

    public function replaceSelected(Request $request)
    {
        $data = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:drivers,id'],
            'replacement_driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $replacementDriver = Driver::findOrFail($data['replacement_driver_id']);
        $protectedAdminIds = [1, 63, 64, 224];
        $orphanUserIds = [];
        $mergedCount = 0;

        DB::transaction(function () use ($data, $replacementDriver, &$orphanUserIds, &$mergedCount) {
            $wrongDrivers = Driver::whereIn('id', $data['selected_ids'])->get();

            foreach ($wrongDrivers as $wrongDriver) {
                if ((int) $wrongDriver->id === (int) $replacementDriver->id) {
                    continue;
                }

                DB::table('plannings')
                    ->where('driver_id', $wrongDriver->id)
                    ->update(['driver_id' => $replacementDriver->id]);

                DB::table('driver_fuel_invoices')
                    ->where('driver_id', $wrongDriver->id)
                    ->update(['driver_id' => $replacementDriver->id]);

                if (!$replacementDriver->user_id && $wrongDriver->user_id) {
                    $replacementDriver->update(['user_id' => $wrongDriver->user_id]);
                    $replacementDriver->refresh();
                } elseif ($wrongDriver->user_id && (int) $wrongDriver->user_id !== (int) $replacementDriver->user_id) {
                    $orphanUserIds[] = (int) $wrongDriver->user_id;
                }

                $wrongDriver->delete();
                $mergedCount++;
            }
        });

        foreach (array_unique($orphanUserIds) as $userId) {
            if (in_array($userId, $protectedAdminIds, true)) {
                continue;
            }

            $hasProfile = DB::table('drivers')->where('user_id', $userId)->exists()
                || DB::table('guides')->where('user_id', $userId)->exists()
                || DB::table('supplier_clients')->where('user_id', $userId)->exists()
                || DB::table('supplier_vehicules')->where('user_id', $userId)->exists();

            $user = User::find($userId);

            if ($user && !$hasProfile) {
                $user->delete();
            }
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', "{$mergedCount} driver(s) remplacé(s). Les plannings ont été transférés vers {$replacementDriver->name}.");
    }

    private function findDuplicateDriver(string $name, ?int $exceptId = null): ?Driver
    {
        $normalized = $this->normalizeDriverName($name);

        return Driver::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get()
            ->first(fn (Driver $driver) => $this->normalizeDriverName($driver->name) === $normalized);
    }

    private function normalizeDriverName(?string $name): string
    {
        $value = (string) Str::of((string) $name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();

        $words = collect(explode(' ', $value))
            ->filter()
            ->sort()
            ->values();

        return $words->implode(' ');
    }
}
