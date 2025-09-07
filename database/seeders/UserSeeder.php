<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Asegura que exista el rol admin en el guard web
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Crea o actualiza al usuario
        $user = User::updateOrCreate(
            ['email' => 'lino@lino'],
            [
                'name' => 'Lino Humanvilca',
                'password' => Hash::make('linoalex'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Asigna (o re-asigna) el rol admin
        // Usa assignRole si quieres mantener roles existentes;
        // usa syncRoles(['admin']) si quieres dejar solo "admin".
        $user->assignRole('admin');
        // $user->syncRoles(['admin']);
    }
}
