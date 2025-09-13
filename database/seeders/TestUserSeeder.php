<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Usamos withoutEvents para que no se disparen Observers (y por tanto, no se envíen correos)
        User::withoutEvents(function () {
            // --- 1. CREAMOS EL USUARIO ---
            $user = User::create([
                'nombres' => 'Carlos',
                'apellidos' => 'Alvarez',
                'email' => 'carlosalvarezbus@gmail.com',
                'password' => Hash::make('password'),
                'rol' => 'usuario',
                'identification_type' => 'CEDULA CIUDANIA',
                'identification_number' => '1234567890',
                'celular' => '3001234567',
            ]);

            // Generamos su código de referido
            $user->referral_code = 'CARLOS12';
            $user->save();

            $this->command->info('Usuario de prueba Carlos Alvarez creado.');

            // --- 2. AÑADIMOS SU SALDO DISPONIBLE ---
            Transaction::create([
                'id_user' => $user->id,
                'tipo' => 'abono',
                'monto' => 5000000,
                'observacion' => 'Saldo inicial de prueba',
            ]);

            $this->command->info('Saldo disponible de $500,000 añadido.');

            // --- 3. CREAMOS SU SUSCRIPCIÓN CERRADA ---
            // Buscamos un plan que sea de tipo 'cerrada'
            $closedPlan = Plan::where('investment_type', 'basica')->first();

            if ($closedPlan) {
                $subscription = $user->subscriptions()->create([
                    'plan_id' => $closedPlan->id,
                    'initial_investment' => 500000,
                    'status' => 'active', // La creamos como activa
                    'contract_type' => 'cerrada',
                ]);

                // --- 4. GENERAMOS SU ÚNICO PAGO A 90 DÍAS ---
                $amount = $subscription->initial_investment;
                $profitPercentage = $closedPlan->closed_profit_percentage ?? 50;
                $durationDays = $closedPlan->closed_duration_days ?? 90;

                $baseProfit = $amount * ($profitPercentage / 100);
                $totalProfit = $baseProfit * 3;
                $totalPayment = $amount + $totalProfit;
                $dueDate = Carbon::now()->addDays($durationDays);

                $subscription->payments()->create([
                    'amount' => $totalPayment,
                    'percentage' => $profitPercentage,
                    'status' => 'pending',
                    'payment_due_date' => $dueDate->toDateString(),
                ]);

                $this->command->info("Suscripción '{$closedPlan->name}' creada con su pago único.");
            } else {
                $this->command->warn('No se encontró un plan de tipo "cerrada" para asignar.');
            }
        });
    }
}