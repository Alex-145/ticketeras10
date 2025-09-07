<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Crea roles base si no existen
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']); // <--- NUEVO

        // Ejemplo si quieres permisos:
        // $p = Permission::firstOrCreate(['name' => 'tickets.view']);
        // Role::whereName('admin')->first()?->givePermissionTo($p);
        // Role::whereName('applicant')->first()?->givePermissionTo($p);
    }
}
