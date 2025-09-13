<?php

namespace App\Http\Controllers\Traits;

use App\Models\Subscription;
use Carbon\Carbon;
use Cmixin\BusinessDay;

trait CreatesPaymentSchedules
{
    protected function createPaymentSchedule(Subscription $subscription)
    {
        $plan = $subscription->plan;
        $amount = $subscription->initial_investment;
        $totalProfit = 0;

        // --- LÓGICA PRINCIPAL BASADA EN EL TIPO DE CONTRATO ---
        if ($subscription->contract_type === 'cerrada') {
            
            $profitPercentage = $plan->closed_profit_percentage ?? 50;
            $durationDays = $plan->closed_duration_days ?? 90;

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

        } else { // 'abierta'
            
            BusinessDay::enable('Carbon\Carbon', 'es_CO');
            $currentDueDate = Carbon::now();
            if (!$currentDueDate->isBusinessDay()) {
                $currentDueDate = $currentDueDate->nextBusinessDay();
            }

            if ($plan->calculation_type === 'fixed_plus_final' && $plan->fixed_percentage) {
                $fixedPayment = $amount * ($plan->fixed_percentage / 100);
                $totalProfit = $fixedPayment * 6;
                
                // --- INICIA EL CÓDIGO QUE FALTABA ---
                for ($i = 1; $i <= 5; $i++) {
                    $subscription->payments()->create([
                        'amount' => $fixedPayment,
                        'percentage' => $plan->fixed_percentage,
                        'status' => 'pending',
                        'payment_due_date' => $currentDueDate->toDateString(),
                    ]);
                    $currentDueDate->addDays(15);
                    if (!$currentDueDate->isBusinessDay()) { $currentDueDate = $currentDueDate->nextBusinessDay(); }
                }
                $finalPayment = $amount + $fixedPayment;
                $subscription->payments()->create([
                    'amount' => $finalPayment,
                    'percentage' => null,
                    'status' => 'pending',
                    'payment_due_date' => $currentDueDate->toDateString(),
                ]);
                // --- FIN DEL CÓDIGO QUE FALTABA ---

            } elseif ($plan->calculation_type === 'equal_installments' && $plan->fixed_percentage) {
                $fixedPayment = $amount * ($plan->fixed_percentage / 100);
                $totalProfit = $fixedPayment * 6;
                $totalToPay = $amount + $totalProfit;
                $installment = $totalToPay / 6;

                for ($i = 1; $i <= 6; $i++) {
                    $subscription->payments()->create([
                        'amount' => $installment,
                        'percentage' => null,
                        'status' => 'pending',
                        'payment_due_date' => $currentDueDate->toDateString(),
                    ]);
                    $currentDueDate->addDays(15);
                    if (!$currentDueDate->isBusinessDay()) { $currentDueDate = $currentDueDate->nextBusinessDay(); }
                }
            }
        }

        // Guardamos la ganancia calculada en la suscripción
        $subscription->profit_amount = $totalProfit;
        $subscription->save();
    }
}