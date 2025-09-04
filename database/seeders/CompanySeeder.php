<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'ABRIL',
            'AGUA',
            'BAMETSA',
            'DyC',
            'EDIFICA',
            'FCM',
            'FDC',
            'GRANADERO',
            'INMGENIO',
            'MADRID',
            'MAESC',
            'MENORCA',
            'OPTIMIZA',
            'PRENISAC',
            'PRODUKTIVA',
            'PROYEC',
            'RT',
            'SAN CHARBEL',
            'URBALIMA',
            'VITAIN',
            'CAPERA',
            'GDC PERU',
            'MAXX',
            'ALMASA',
            'DITRENZZO',
            'ACTUAL ANDINA',
            'ORTIZ INMOBILIARIA',
            'GOHOUSE',
            'DKASA',
            'EVERGRAN',
            'CISSAC',
            'LATERAL',
            'PADOVA',
            'ECO HOUSE',
            'TRIBECA',
            'PROYECTA',
            'REDBAY',
            'MARCAN',
            'REYNA',
            'TACTICAL',
            'OBERTI',
            'CHECOR'
        ];

        foreach ($companies as $name) {
            DB::table('companies')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
