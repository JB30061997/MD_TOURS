<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndDefaultAdminsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'administrateur',
            'guide',
            'driver',
            'supplier_client',
            'supplier_vehicule',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        User::whereIn('id', [1, 2])->get()->each(function ($user) {
            $user->syncRoles(['admin']);
            $user->update([
                'active' => 1,
            ]);
        });
    }
}