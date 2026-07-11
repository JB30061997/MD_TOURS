<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Support\PermissionRegistry;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionRegistry::permissions() as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin       = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminassistant       = Role::firstOrCreate(['name' => 'adminassistant', 'guard_name' => 'web']);
        $technicien  = Role::firstOrCreate(['name' => 'technicien', 'guard_name' => 'web']);
        $userCompany       = Role::firstOrCreate(['name' => 'userCompany', 'guard_name' => 'web']);
        $comptable         = Role::firstOrCreate(['name' => 'comptable', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::pluck('name')->toArray());
        $admin->syncPermissions(Permission::pluck('name')->toArray());
        $adminassistant->syncPermissions(Permission::pluck('name')->toArray());

        $technicien->syncPermissions([
            'dashboard.view',
            'plannings.view',
            'clients.view',
        ]);

        $userCompany->syncPermissions([
            'dashboard.view',
            'plannings.view',
        ]);

        $comptable->givePermissionTo('driver-primes.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
