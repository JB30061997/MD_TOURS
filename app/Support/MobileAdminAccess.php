<?php

namespace App\Support;

use App\Models\User;

class MobileAdminAccess
{
    public const PRIVILEGED_ROLES = [
        'super_admin',
        'super admin',
        'admin',
        'administrateur',
        'administrator',
        'manager',
    ];

    public const MODULE_PERMISSIONS = [
        'dashboard' => 'dashboard.view',
        'plannings' => 'plannings.view',
        'commandes' => 'commandes.view',
        'road_sheets' => 'road-sheets.view',
        'drivers' => 'drivers.view',
        'guides' => 'guides.view',
        'vehicles' => 'vehicules.view',
        'clients' => 'clients.view',
        'supplier_clients' => 'supplier-clients.view',
        'supplier_vehicles' => 'supplier-vehicules.view',
        'supplier_control' => 'supplier-vehicule-tarifs.view',
        'maintenance' => 'vehicle-maintenances.view',
        'mailbox' => 'mailbox.view',
        'supplier_invoices' => 'supplier-vehicule-invoices.view',
    ];

    public function allowed(User $user): bool
    {
        return $this->hasPrivilegedRole($user) || $user->can('dashboard.view');
    }

    public function can(User $user, string $permission): bool
    {
        return $this->hasPrivilegedRole($user) || $user->can($permission);
    }

    public function modules(User $user): array
    {
        $all = $this->hasPrivilegedRole($user);

        return collect(self::MODULE_PERMISSIONS)
            ->mapWithKeys(fn (string $permission, string $module) => [
                $module => $all || $user->can($permission),
            ])
            ->all();
    }

    public function payload(User $user): array
    {
        return [
            'area' => $this->allowed($user) ? 'admin' : 'driver',
            'is_admin' => $this->allowed($user),
            'modules' => $this->modules($user),
        ];
    }

    private function hasPrivilegedRole(User $user): bool
    {
        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(self::PRIVILEGED_ROLES);
    }
}
