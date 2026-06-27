<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverFuelCard;
use App\Models\Vehicule;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureDriverOperationTablesExist();

        $search = $request->search;
        $driverRelations = [];

        if (Schema::hasTable('driver_vehicle_assignments')) {
            $driverRelations[] = 'currentVehicleAssignment.vehicule';
        }

        if (Schema::hasTable('driver_fuel_cards')) {
            $driverRelations['fuelCards'] = fn ($query) => $query->latest();

            if (Schema::hasTable('driver_fuel_card_transactions')) {
                $driverRelations['fuelCards.transactions'] = fn ($query) => $query->latest()->limit(6);
            }
        }

        $drivers = Driver::query()
            ->with($driverRelations)
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
            'vehicules' => Vehicule::query()
                ->orderBy('matricule')
                ->get(['id', 'matricule', 'marque', 'modele', 'status']),
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
        $this->ensureDriverOperationTablesExist();

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

                DB::table('driver_vehicle_assignments')
                    ->where('driver_id', $wrongDriver->id)
                    ->update(['driver_id' => $replacementDriver->id]);

                DB::table('driver_fuel_cards')
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

    public function assignVehicle(Request $request, Driver $driver)
    {
        $this->ensureDriverOperationTablesExist();

        $data = $request->validate([
            'vehicule_id' => ['required', 'exists:vehicules,id'],
            'assigned_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($driver, $data) {
                DB::table('driver_vehicle_assignments')
                    ->where('driver_id', $driver->id)
                    ->whereNull('released_date')
                    ->update(['released_date' => $data['assigned_date'], 'updated_at' => now()]);

                DB::table('driver_vehicle_assignments')
                    ->where('vehicule_id', $data['vehicule_id'])
                    ->whereNull('released_date')
                    ->update(['released_date' => $data['assigned_date'], 'updated_at' => now()]);

                $driver->vehicleAssignments()->create([
                    'vehicule_id' => $data['vehicule_id'],
                    'assigned_date' => $data['assigned_date'],
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('Driver vehicle assignment failed', [
                'driver_id' => $driver->id,
                'vehicule_id' => $data['vehicule_id'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible d’affecter ce véhicule. Vérifiez les données puis réessayez.');
        }

        return back()->with('success', 'Véhicule affecté au driver avec succès.');
    }

    public function releaseVehicle(Request $request, Driver $driver)
    {
        $this->ensureDriverOperationTablesExist();

        $data = $request->validate([
            'released_date' => ['required', 'date'],
        ]);

        $assignment = $driver->currentVehicleAssignment()->first();

        if ($assignment) {
            try {
                $assignment->update(['released_date' => $data['released_date']]);
            } catch (\Throwable $e) {
                Log::warning('Driver vehicle release failed', [
                    'driver_id' => $driver->id,
                    'message' => $e->getMessage(),
                ]);

                return back()->with('error', 'Impossible de libérer ce véhicule. Vérifiez les données puis réessayez.');
            }
        }

        return back()->with('success', 'Véhicule libéré avec succès.');
    }

    public function storeFuelCard(Request $request, Driver $driver)
    {
        $this->ensureDriverOperationTablesExist();

        $data = $request->validate([
            'card_number' => ['required', 'string', 'max:255', 'unique:driver_fuel_cards,card_number'],
            'label' => ['nullable', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($driver, $data) {
                $initialBalance = (float) ($data['initial_balance'] ?? 0);

                $card = $driver->fuelCards()->create([
                    'card_number' => $data['card_number'],
                    'label' => $data['label'] ?? null,
                    'balance' => $initialBalance,
                    'status' => $data['status'] ?? 'active',
                    'notes' => $data['notes'] ?? null,
                ]);

                if ($initialBalance > 0) {
                    $card->transactions()->create([
                        'type' => 'recharge',
                        'amount' => $initialBalance,
                        'transaction_date' => now()->toDateString(),
                        'reference' => 'Solde initial',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Driver fuel card creation failed', [
                'driver_id' => $driver->id,
                'card_number' => $data['card_number'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible d’ajouter cette carte gasoil. Vérifiez les données puis réessayez.');
        }

        return back()->with('success', 'Carte gasoil ajoutée avec succès.');
    }

    public function storeFuelCardTransaction(Request $request, Driver $driver, DriverFuelCard $fuelCard)
    {
        $this->ensureDriverOperationTablesExist();

        if ((int) $fuelCard->driver_id !== (int) $driver->id) {
            abort(404);
        }

        $data = $request->validate([
            'type' => ['required', 'in:recharge,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($fuelCard, $data) {
                $amount = (float) $data['amount'];

                if ($data['type'] === 'expense' && (float) $fuelCard->balance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Solde insuffisant pour cette carte gasoil.',
                    ]);
                }

                $fuelCard->transactions()->create([
                    'type' => $data['type'],
                    'amount' => $amount,
                    'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $fuelCard->update([
                    'balance' => $data['type'] === 'recharge'
                        ? (float) $fuelCard->balance + $amount
                        : (float) $fuelCard->balance - $amount,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Driver fuel card transaction failed', [
                'driver_id' => $driver->id,
                'fuel_card_id' => $fuelCard->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible d’enregistrer ce mouvement gasoil. Vérifiez les données puis réessayez.');
        }

        return back()->with('success', 'Mouvement carte gasoil enregistré avec succès.');
    }

    public function updateFuelCardStatus(Request $request, Driver $driver, DriverFuelCard $fuelCard)
    {
        $this->ensureDriverOperationTablesExist();

        if ((int) $fuelCard->driver_id !== (int) $driver->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            $fuelCard->update(['status' => $data['status']]);
        } catch (\Throwable $e) {
            Log::warning('Driver fuel card status update failed', [
                'driver_id' => $driver->id,
                'fuel_card_id' => $fuelCard->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de modifier le statut de cette carte.');
        }

        return back()->with('success', 'Status carte gasoil mis à jour.');
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

    private function ensureDriverOperationTablesExist(): void
    {
        if (!Schema::hasTable('driver_vehicle_assignments')) {
            Schema::create('driver_vehicle_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
                $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $table->date('assigned_date');
                $table->date('released_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumn('driver_vehicle_assignments', 'driver_id', fn (Blueprint $table) => $table->foreignId('driver_id')->nullable()->constrained('drivers')->cascadeOnDelete());
        $this->ensureColumn('driver_vehicle_assignments', 'vehicule_id', fn (Blueprint $table) => $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->cascadeOnDelete());
        $this->ensureColumn('driver_vehicle_assignments', 'assigned_date', fn (Blueprint $table) => $table->date('assigned_date')->nullable());
        $this->ensureColumn('driver_vehicle_assignments', 'released_date', fn (Blueprint $table) => $table->date('released_date')->nullable());
        $this->ensureColumn('driver_vehicle_assignments', 'notes', fn (Blueprint $table) => $table->text('notes')->nullable());
        $this->ensureColumn('driver_vehicle_assignments', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->ensureColumn('driver_vehicle_assignments', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());

        if (!Schema::hasTable('driver_fuel_cards')) {
            Schema::create('driver_fuel_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
                $table->string('card_number')->unique();
                $table->string('label')->nullable();
                $table->decimal('balance', 12, 2)->default(0);
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumn('driver_fuel_cards', 'driver_id', fn (Blueprint $table) => $table->foreignId('driver_id')->nullable()->constrained('drivers')->cascadeOnDelete());
        $this->ensureColumn('driver_fuel_cards', 'card_number', fn (Blueprint $table) => $table->string('card_number')->nullable());
        $this->ensureColumn('driver_fuel_cards', 'label', fn (Blueprint $table) => $table->string('label')->nullable());
        $this->ensureColumn('driver_fuel_cards', 'balance', fn (Blueprint $table) => $table->decimal('balance', 12, 2)->default(0));
        $this->ensureColumn('driver_fuel_cards', 'status', fn (Blueprint $table) => $table->string('status')->default('active'));
        $this->ensureColumn('driver_fuel_cards', 'notes', fn (Blueprint $table) => $table->text('notes')->nullable());
        $this->ensureColumn('driver_fuel_cards', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->ensureColumn('driver_fuel_cards', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());

        if (!Schema::hasTable('driver_fuel_card_transactions')) {
            Schema::create('driver_fuel_card_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_fuel_card_id')->constrained('driver_fuel_cards')->cascadeOnDelete();
                $table->string('type');
                $table->decimal('amount', 12, 2);
                $table->date('transaction_date')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumn('driver_fuel_card_transactions', 'driver_fuel_card_id', fn (Blueprint $table) => $table->foreignId('driver_fuel_card_id')->nullable()->constrained('driver_fuel_cards')->cascadeOnDelete());
        $this->ensureColumn('driver_fuel_card_transactions', 'type', fn (Blueprint $table) => $table->string('type')->nullable());
        $this->ensureColumn('driver_fuel_card_transactions', 'amount', fn (Blueprint $table) => $table->decimal('amount', 12, 2)->default(0));
        $this->ensureColumn('driver_fuel_card_transactions', 'transaction_date', fn (Blueprint $table) => $table->date('transaction_date')->nullable());
        $this->ensureColumn('driver_fuel_card_transactions', 'reference', fn (Blueprint $table) => $table->string('reference')->nullable());
        $this->ensureColumn('driver_fuel_card_transactions', 'notes', fn (Blueprint $table) => $table->text('notes')->nullable());
        $this->ensureColumn('driver_fuel_card_transactions', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->ensureColumn('driver_fuel_card_transactions', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureColumn(string $tableName, string $columnName, callable $definition): void
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
}
