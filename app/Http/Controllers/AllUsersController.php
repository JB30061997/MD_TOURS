<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AllUsersController extends Controller
{
    private const PROFILE_ROLES = [
        'admin',
        'guide',
        'driver',
        'supplier_client',
        'supplier_vehicule',
    ];

    private const PROTECTED_ADMIN_IDS = [1, 63, 64, 224];

    public function index(Request $request)
    {
        try {

            $this->ensureRoles();
            $this->linkProfilesToMatchingUsers();
            $this->syncRolesFromLinkedProfiles();

            $search = $request->search;
            $role = $request->role;
            $status = $request->status;

            $users = User::with([
                'roles',
                'driver',
                'guide',
                'supplierClients',
                'supplierVehicules',
            ])
                ->when($search, function ($query) use ($search) {
                    $emailSearch = Str::of($search)
                        ->lower()
                        ->ascii()
                        ->replaceMatches('/[^a-z0-9]+/', '-')
                        ->trim('-');

                    $query->where(function ($q) use ($search, $emailSearch) {
                        $q->where('name', 'like', "%{$search}%");

                        if ((string) $emailSearch !== '') {
                            $q->orWhereRaw(
                                'LOWER(SUBSTRING_INDEX(email, "@", 1)) LIKE ?',
                                ['%' . $emailSearch . '%']
                            );
                        }
                    });
                })

                ->when($role, function ($query) use ($role) {

                    // verification role kayna
                    if (\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                        $query->role($role);
                    }
                })

                ->when($status, function ($query) use ($status) {

                    if ($status === 'active') {
                        $query->where('active', 1);
                    }

                    if ($status === 'inactive') {
                        $query->where('active', 0);
                    }
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
                        'created_at' => optional($user->created_at)->format('d M Y H:i'),

                        'role' => optional($user->roles->first())->name,

                        'driver_id' => optional($user->driver)->id,
                        'guide_id' => optional($user->guide)->id,
                        'supplier_client_id' => optional($user->supplierClients->first())->id,
                        'supplier_vehicule_id' => optional($user->supplierVehicules->first())->id,
                        'mail_integrate' => (bool) $user->mail_integrate,
                        'mail_integration_login' => $user->mail_integration_login,
                        'mail_integration_ready' => (bool) ($user->mail_integrate && $user->mail_integration_login && $user->mail_integration_password),

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
                    'search' => $search,
                    'role' => $role,
                    'status' => $status,
                ],

                'roles' => self::PROFILE_ROLES,
                'protectedAdminIds' => self::PROTECTED_ADMIN_IDS,

                'drivers' => Driver::select('id', 'name', 'user_id')
                    ->orderBy('name')
                    ->get(),

                'guides' => Guide::select('id', 'name', 'user_id')
                    ->orderBy('name')
                    ->get(),

                'supplierClients' => SupplierClient::select('id', 'name', 'user_id')
                    ->orderBy('name')
                    ->get(),

                'supplierVehicules' => SupplierVehicule::select('id', 'name', 'user_id')
                    ->orderBy('name')
                    ->get(),
            ]);
        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Unexpected error: ' . $e->getMessage()
            );
        }
    }


    private function ensureRoles(): void
    {
        foreach (self::PROFILE_ROLES as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();
    }

    private function syncRolesFromLinkedProfiles(): void
    {
        User::with(['driver', 'guide', 'supplierClients', 'supplierVehicules', 'roles'])
            ->get()
            ->each(function (User $user) {
                $role = $this->roleForUserProfile($user);

                if (!$role) {
                    if ($user->roles->isNotEmpty()) {
                        $user->syncRoles([]);
                    }

                    return;
                }

                if (!$user->hasRole($role) || $user->roles->count() !== 1) {
                    $user->syncRoles([$role]);
                }
            });

        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();
    }

    private function roleForUserProfile(User $user): ?string
    {
        if ($this->isProtectedAdmin($user)) {
            return 'admin';
        }

        if ($user->driver) {
            return 'driver';
        }

        if ($user->guide) {
            return 'guide';
        }

        if ($user->supplierClients->isNotEmpty()) {
            return 'supplier_client';
        }

        if ($user->supplierVehicules->isNotEmpty()) {
            return 'supplier_vehicule';
        }

        return null;
    }

    private function linkProfilesToMatchingUsers(): void
    {
        User::with(['driver', 'guide', 'supplierClients', 'supplierVehicules'])
            ->get()
            ->each(function (User $user) {
                if ($this->isProtectedAdmin($user) || $this->userHasLinkedProfile($user)) {
                    return;
                }

                $match = $this->findSingleUnlinkedProfileForUser($user);

                if (!$match) {
                    return;
                }

                [$model, $profile] = $match;

                $model::whereKey($profile->id)->update([
                    'user_id' => $user->id,
                ]);
            });
    }

    private function userHasLinkedProfile(User $user): bool
    {
        return $user->driver
            || $user->guide
            || $user->supplierClients->isNotEmpty()
            || $user->supplierVehicules->isNotEmpty();
    }

    private function findSingleUnlinkedProfileForUser(User $user): ?array
    {
        $models = [
            Driver::class,
            Guide::class,
            SupplierClient::class,
            SupplierVehicule::class,
        ];

        $emailMatches = collect();

        foreach ($models as $model) {
            if (!$this->profileModelHasColumn($model, 'email')) {
                continue;
            }

            $profile = $model::query()
                ->whereNull('user_id')
                ->whereNotNull('email')
                ->whereRaw('LOWER(email) = ?', [Str::lower($user->email)])
                ->first();

            if ($profile) {
                $emailMatches->push([$model, $profile]);
            }
        }

        if ($emailMatches->count() === 1) {
            return $emailMatches->first();
        }

        $nameMatches = collect();
        $normalizedUserName = $this->normalizeProfileName($user->name);

        if (!$normalizedUserName) {
            return null;
        }

        foreach ($models as $model) {
            $profiles = $model::query()
                ->whereNull('user_id')
                ->get();

            $profiles
                ->filter(fn ($profile) => $this->normalizeProfileName($profile->name) === $normalizedUserName)
                ->each(fn ($profile) => $nameMatches->push([$model, $profile]));
        }

        return $nameMatches->count() === 1 ? $nameMatches->first() : null;
    }

    private function profileModelHasColumn(string $model, string $column): bool
    {
        return in_array($column, (new $model())->getFillable(), true);
    }

    private function isProtectedAdmin(User $user): bool
    {
        return in_array((int) $user->id, self::PROTECTED_ADMIN_IDS, true);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],

                'role' => ['required', Rule::in(self::PROFILE_ROLES)],

                'active' => ['required', 'boolean'],

                'driver_id' => ['nullable', 'exists:drivers,id'],
                'guide_id' => ['nullable', 'exists:guides,id'],
                'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
                'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
            ]);

            if ($data['role'] === 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'Only protected administrator accounts can use the Administrator role.',
                ]);
            }

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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            return back()->with(
                'error',
                'Create failed: ' . $th->getMessage()
            );
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],

                'password' => ['nullable', 'string', 'min:6'],

                'role' => ['required', Rule::in(self::PROFILE_ROLES)],

                'active' => ['required', 'boolean'],

                'driver_id' => ['nullable', 'exists:drivers,id'],
                'guide_id' => ['nullable', 'exists:guides,id'],
                'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
                'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
            ]);

            if ($this->isProtectedAdmin($user)) {
                $data['role'] = 'admin';
                $request->merge(['role' => 'admin']);
            } elseif ($data['role'] === 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'Only protected administrator accounts can use the Administrator role.',
                ]);
            }

            $this->prepareProfileForRole($request, $user);
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            return back()->with(
                'error',
                'Update failed: ' . $th->getMessage()
            );
        }
    }

    public function destroy(User $user)
    {
        if ($this->isProtectedAdmin($user)) {
            return back()->with(
                'error',
                'Vous ne pouvez pas supprimer un compte administrateur protégé.'
            );
        }

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
        if ($this->isProtectedAdmin($user)) {
            return back()->with(
                'error',
                'Vous ne pouvez pas désactiver un compte administrateur protégé.'
            );
        }

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

            $this->validateProfileIsAvailable(Driver::class, $request->driver_id);
        }

        if ($request->role === 'guide') {
            $request->validate([
                'guide_id' => ['required', 'exists:guides,id'],
            ]);

            $this->validateProfileIsAvailable(Guide::class, $request->guide_id);
        }

        if ($request->role === 'supplier_client') {
            $request->validate([
                'supplier_client_id' => ['required', 'exists:supplier_clients,id'],
            ]);

            $this->validateProfileIsAvailable(SupplierClient::class, $request->supplier_client_id);
        }

        if ($request->role === 'supplier_vehicule') {
            $request->validate([
                'supplier_vehicule_id' => ['required', 'exists:supplier_vehicules,id'],
            ]);

            $this->validateProfileIsAvailable(SupplierVehicule::class, $request->supplier_vehicule_id);
        }
    }

    private function prepareProfileForRole(Request $request, User $user): void
    {
        if ($request->role === 'driver' && !$request->filled('driver_id')) {
            $driver = $this->findAvailableProfileForUser(Driver::class, $user);

            if ($driver) {
                $request->merge(['driver_id' => $driver->id]);
            }
        }

        if ($request->role === 'guide' && !$request->filled('guide_id')) {
            $guide = $this->findAvailableProfileForUser(Guide::class, $user);

            if ($guide) {
                $request->merge(['guide_id' => $guide->id]);
            }
        }

        if ($request->role === 'supplier_client' && !$request->filled('supplier_client_id')) {
            $supplierClient = $this->findAvailableProfileForUser(SupplierClient::class, $user);

            if ($supplierClient) {
                $request->merge(['supplier_client_id' => $supplierClient->id]);
            }
        }

        if ($request->role === 'supplier_vehicule' && !$request->filled('supplier_vehicule_id')) {
            $supplierVehicule = $this->findAvailableProfileForUser(SupplierVehicule::class, $user);

            if ($supplierVehicule) {
                $request->merge(['supplier_vehicule_id' => $supplierVehicule->id]);
            }
        }
    }

    private function findAvailableProfileForUser(string $model, User $user): mixed
    {
        $profiles = $model::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->get();

        return $profiles->firstWhere('user_id', $user->id)
            ?? $profiles->first(fn ($profile) => $profile->email && Str::lower($profile->email) === Str::lower($user->email))
            ?? $profiles->first(fn ($profile) => $this->normalizeProfileName($profile->name) === $this->normalizeProfileName($user->name));
    }

    private function normalizeProfileName(?string $name): string
    {
        $value = (string) Str::of((string) $name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();

        return collect(explode(' ', $value))
            ->filter()
            ->sort()
            ->values()
            ->implode(' ');
    }

    private function validateProfileIsAvailable(string $model, mixed $profileId): void
    {
        $profile = $model::find($profileId);

        if ($profile && $profile->user_id && (int) $profile->user_id !== (int) request()->route('user')?->id) {
            throw ValidationException::withMessages([
                $this->profileFieldForModel($model) => 'Ce profile est déjà affecté à un autre utilisateur.',
            ]);
        }
    }

    private function profileFieldForModel(string $model): string
    {
        return match ($model) {
            Driver::class => 'driver_id',
            Guide::class => 'guide_id',
            SupplierClient::class => 'supplier_client_id',
            SupplierVehicule::class => 'supplier_vehicule_id',
            default => 'profile_id',
        };
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
