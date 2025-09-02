<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon; 
use App\Models\Plan;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->rol === 'admin') {
            return redirect()->route('admin.users.index');
        }

        $abonos = $user->transactions()->where('tipo', 'abono')->sum('monto');
        $retiros = $user->transactions()->where('tipo', 'retiro')->sum('monto');
        $totalAvailable = $abonos - $retiros;

        // 1. Obtenemos TODAS las suscripciones activas con sus relaciones
        $activeSubscriptions = $user->subscriptions()
            ->with(['plan', 'payments' => function ($query) {
                $query->orderBy('payment_due_date', 'asc');
            }])
            ->where('status', 'active')
            ->get();

        $plans = Plan::all(); 
        $transactions = $user->transactions()->latest()->get();


        $allPayments = $activeSubscriptions->flatMap(function ($subscription) {
            return $subscription->payments;
        });

        // 2. Calculamos los TOTALES GLOBALES para las tarjetas de resumen
        $totalInversion = $activeSubscriptions->sum('initial_investment');
        $totalUtilidad = 0;
        
        foreach ($activeSubscriptions as $sub) {
            if ($sub->plan->calculation_type === 'fixed_plus_final') {
                $inversion = $sub->initial_investment;
                $fixedPayment = $inversion * ($sub->plan->fixed_percentage / 100);
                $totalUtilidad += $fixedPayment * 6;
            }
            // Aquí podrías añadir la lógica de utilidad para otros planes
        }

        $totalGanancia = $totalInversion + $totalUtilidad;
        
        // 3. Enviamos la colección de suscripciones y los totales a la vista
        return Inertia::render('Dashboard', [
            'subscriptions' => $activeSubscriptions,
            'totalInversion' => $totalInversion,
            'totalUtilidad' => $totalUtilidad,
            'totalGanancia' => $totalGanancia,
            'plans' => $plans, 
            'totalAvailable' => $totalAvailable,
            'transactions' => $transactions,
        ]);
    }
}