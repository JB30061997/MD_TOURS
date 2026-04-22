<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ had l'array dyal permissions, zayd/na9as 3la hsab projet
        $permissions = [
            // users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete'
        ];

        // 🔁 nsta3mlo firstOrCreate bach ma ytkarrawch ila 3awdt seedit
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 👥 roles
        $admin       = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminassistant       = Role::firstOrCreate(['name' => 'adminassistant', 'guard_name' => 'web']);
        $technicien  = Role::firstOrCreate(['name' => 'technicien', 'guard_name' => 'web']);
        $userCompany       = Role::firstOrCreate(['name' => 'userCompany', 'guard_name' => 'web']);

        // 🧩 assignment dyal permissions lkol role
        $admin->syncPermissions(Permission::pluck('name')->toArray());

        $adminassistant->syncPermissions(Permission::pluck('name')->toArray());

        $technicien->syncPermissions([
            'users.view',
            'users.create',
        ]);

        $userCompany->syncPermissions([
            'users.view',
            'users.edit',
        ]);
    }
}
