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
            'description' => 'Un plan avanzado con mayores beneficios.',
            'image_url' => 'https://placehold.co/600x400/EAB308/FFF?text=Plan+Premium', // <-- Añade esto
            'calculation_type' => 'percentage_based',
            'fixed_percentage' => null,
            'percentages' => [8, 12, 18, 22, 28, 35],
        ]);
    }
}
