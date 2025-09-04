<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Error',
            'Consultoria',
            'Manejo',
            'Accesos',
            'Configuracion',
            'Capacitacion',
            'Reporteria',
            'Requerimiento',
            'Aplicativo',
            'Query',
            'BD',
            'Instalacion'
        ];

        foreach ($categories as $name) {
            DB::table('categories')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
