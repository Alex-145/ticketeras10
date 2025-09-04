<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'APP APROBACION',
            'APP PEDIDOS',
            'GENERAL',
            'BD',
            'SPERANT',
            'POWER Bi',
            'FACTURACION ELECTRONICA',
            'ALMACENES',
            'ADMINISTRATIVO',
            'COMPRAS',
            'GOOGLE',
            'CONTABILIDAD',
            'FACTURACION',
            'GERENCIA DE PROYECTOS',
            'GESTION',
            'LEAN',
            'NOMINAS',
            'PEDIDOS',
            'PRESUPUESTO',
            'SALERP',
            'VALORIZACION',
            'S10 TIKET',
            'PORTAL DE PROVEEDOR',
            'PORTAL DE COLABORADOR',
            'TAREO MOVIL',
            'APROBACION MOVIL',
            'PEDIDOS MOVIL'
        ];

        foreach ($modules as $name) {
            DB::table('modules')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
