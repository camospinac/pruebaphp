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

        if ($subscription->contract_type === 'cerrada') {

            // LÓGICA UNIVERSAL PARA CONTRATO CERRADO
            $amount = $subscription->initial_investment;
            $profitPercentage = $plan->closed_profit_percentage ?? 40;
            $durationDays = $plan->closed_duration_days ?? 90;
            // --- INICIA LA NUEVA FÓRMULA ---
            $baseProfit = $amount * ($profitPercentage / 100);
            $totalProfit = $baseProfit * 6;
            $totalPayment = $amount + $totalProfit;
            
            $dueDate = Carbon::now()->addDays($durationDays);

            $subscription->payments()->create([
                'amount' => $totalPayment,
                'percentage' => $profitPercentage,
                'status' => 'pending',
                'payment_due_date' => $dueDate->toDateString(),
            ]);
        } else {

            BusinessDay::enable('Carbon\Carbon', 'es_CO');
            $currentDueDate = Carbon::now();
            if (!$currentDueDate->isBusinessDay()) {
                $currentDueDate = $currentDueDate->nextBusinessDay();
            }


            if ($plan->calculation_type === 'lump_sum') {
                $amount = $subscription->initial_investment;
                $profit = $amount * ($plan->fixed_percentage / 100);
                $totalPayment = $amount + $profit;
                $dueDate = Carbon::now()->addDays(90); // 90 días calendario

                $subscription->payments()->create([
                    'amount' => $totalPayment,
                    'percentage' => $plan->fixed_percentage,
                    'status' => 'pending',
                    'payment_due_date' => $dueDate->toDateString(),
                ]);
            } elseif ($plan->calculation_type === 'fixed_plus_final') {
                $amount = $subscription->initial_investment;
                $fixedPayment = $amount * ($plan->fixed_percentage / 100);

                for ($i = 1; $i <= 5; $i++) {
                    $subscription->payments()->create([
                        'amount' => $fixedPayment,
                        'percentage' => $plan->fixed_percentage,
                        'status' => 'pending',
                        'payment_due_date' => $currentDueDate->toDateString(),
                    ]);
                    $currentDueDate->addDays(15);
                    if (!$currentDueDate->isBusinessDay()) {
                        $currentDueDate = $currentDueDate->nextBusinessDay();
                    }
                }
                $finalPayment = $amount + $fixedPayment;
                $subscription->payments()->create([
                    'amount' => $finalPayment,
                    'percentage' => null,
                    'status' => 'pending',
                    'payment_due_date' => $currentDueDate->toDateString(),
                ]);
            } elseif ($plan->calculation_type === 'equal_installments') {
                $amount = $subscription->initial_investment;
                $fixedPayment = $amount * ($plan->fixed_percentage / 100);
                $totalProfit = $fixedPayment * 6;
                $totalToPay = $amount + $totalProfit;
                $installment = $totalToPay / 6;

                for ($i = 1; $i <= 6; $i++) {
                    $subscription->payments()->create([
                        'amount' => $installment,
                        'percentage' => null, // No aplica un porcentaje por cuota
                        'status' => 'pending',
                        'payment_due_date' => $currentDueDate->toDateString(),
                    ]);
                    $currentDueDate->addDays(15);
                    if (!$currentDueDate->isBusinessDay()) {
                        $currentDueDate = $currentDueDate->nextBusinessDay();
                    }
                }
            }
        }
    }
}
