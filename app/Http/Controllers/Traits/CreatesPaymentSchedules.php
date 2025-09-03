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

        BusinessDay::enable('Carbon\Carbon', 'es_CO');
        $currentDueDate = Carbon::now();
        if (!$currentDueDate->isBusinessDay()) {
            $currentDueDate = $currentDueDate->nextBusinessDay();
        }

        if ($plan->calculation_type === 'fixed_plus_final') {
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