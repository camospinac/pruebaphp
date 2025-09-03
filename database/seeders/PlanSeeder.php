<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\Plan; // Importamos el modelo Plan

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Plan Básico',
            'description' => '5 cuotas fijas y una cuota final con el capital.',
            'image_url' => 'https://placehold.co/600x400/0EA5E9/FFF?text=Plan+Basico', // <-- Añade esto
            'calculation_type' => 'fixed_plus_final',
            'fixed_percentage' => 15.00,
            'percentages' => null,
        ]);

        Plan::create([
            'name' => 'Plan Premium',
            'description' => 'Paga tu inversión y utilidad en 6 cuotas iguales y fijas.',
            'image_url' => 'https://placehold.co/600x400/EAB308/FFF?text=Plan+Premium',
            'calculation_type' => 'equal_installments', // <-- TIPO DE CÁLCULO
            'fixed_percentage' => 15.00,             // <-- PORCENTAJE FIJO
            'percentages' => null,                   // <-- ESTO DEBE SER NULO
        ]);
    }
}
