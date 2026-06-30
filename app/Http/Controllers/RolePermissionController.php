<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index(): Response
    {
        $this->ensureRegistryPermissions();

        return Inertia::render('RolesPermissions/Index', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name')->values(),
                    'locked' => in_array($role->name, ['super_admin', 'admin'], true),
                ]),
            'permissions' => Permission::query()
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name']),
            'permissionGroups' => PermissionRegistry::grouped(),
            'users' => User::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'active'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => (bool) $user->active,
                    'roles' => $user->roles->pluck('name')->values(),
                ]),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\\-]+$/', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Rôle créé avec succès.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\\-]+$/', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        if (!in_array($role->name, ['super_admin', 'admin'], true)) {
            $role->update(['name' => $data['name']]);
        }

        if (in_array($role->name, ['super_admin', 'admin'], true)) {
            $role->syncPermissions(Permission::pluck('name')->all());
        } else {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Rôle mis à jour avec succès.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['super_admin', 'admin'], true)) {
            return back()->with('error', 'Ce rôle est protégé.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Ce rôle est affecté à des utilisateurs.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Rôle supprimé avec succès.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_\\-]+\\.[a-z0-9_\\-]+$/', Rule::unique('permissions', 'name')],
        ]);

        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $this->syncSuperAdminPermissions();

        return back()->with('success', 'Permission créée avec succès.');
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_\\-]+\\.[a-z0-9_\\-]+$/', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update(['name' => $data['name']]);
        $this->syncSuperAdminPermissions();

        return back()->with('success', 'Permission mise à jour avec succès.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $permission->delete();
        $this->syncSuperAdminPermissions();

        return back()->with('success', 'Permission supprimée avec succès.');
    }

    public function assignUserRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        if ($this->wouldRemoveLastSuperAdmin($user, $data['role'])) {
            return back()->with('error', 'Impossible de retirer le dernier Super Admin.');
        }

        $user->syncRoles([$data['role']]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Rôle utilisateur mis à jour.');
    }

    private function ensureRegistryPermissions(): void
    {
        foreach (PermissionRegistry::permissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->syncSuperAdminPermissions();
    }

    private function syncSuperAdminPermissions(): void
    {
        $permissions = Permission::pluck('name')->all();

        foreach (['super_admin', 'admin'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function wouldRemoveLastSuperAdmin(User $user, string $newRole): bool
    {
        if ($newRole === 'super_admin' || !$user->hasRole('super_admin')) {
            return false;
        }

        return User::role('super_admin')->whereKeyNot($user->id)->count() === 0;
    }
}
