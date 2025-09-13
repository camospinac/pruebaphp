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
            return redirect()->route('admin.dashboard');
        }

        // --- CÁLCULOS PRINCIPALES ---

        // 1. Obtenemos TODAS las suscripciones activas (con su plan para los cálculos)
        $activeSubscriptions = $user->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->get();

        // 2. Calculamos los TOTALES GLOBALES para las tarjetas
        $totalInversion = $activeSubscriptions->sum('initial_investment');
        $totalUtilidad = 0;
        
        // --- INICIA LA LÓGICA CORREGIDA ---
        // Iteramos sobre cada suscripción para calcular su utilidad específica
        foreach ($activeSubscriptions as $sub) {
            // Si el contrato es CERRADO, usamos el porcentaje para cerrados (40%)
            if ($sub->contract_type === 'cerrada' && $sub->plan->closed_profit_percentage) {
                $baseProfit = $sub->initial_investment * ($sub->plan->closed_profit_percentage / 100);
                $totalUtilidad += $baseProfit * 3; // La fórmula que definimos
            } 
            // Si el contrato es ABIERTO, usamos el porcentaje para abiertos (15%)
            elseif ($sub->contract_type === 'abierta' && $sub->plan->fixed_percentage) {
                $baseProfit = $sub->initial_investment * ($sub->plan->fixed_percentage / 100);
                $totalUtilidad += $baseProfit * 6;
            }
        }
        // --- FIN DE LA LÓGICA CORREGIDA ---

        $totalGanancia = $totalInversion + $totalUtilidad;
        
        // 3. Calculamos el saldo disponible desde la tabla de transacciones
        $abonos = $user->transactions()->where('tipo', 'abono')->sum('monto');
        $retiros = $user->transactions()->where('tipo', 'retiro')->sum('monto');
        $totalAvailable = $abonos - $retiros;

        // 4. Preparamos los datos para enviar a la vista
        return Inertia::render('Dashboard', [
            // Cargamos los pagos aquí al final para mantener la consulta inicial ligera
            'subscriptions' => $activeSubscriptions->load(['payments' => function ($query) {
                $query->orderBy('payment_due_date', 'asc');
            }]),
            'plans' => Plan::all(),
            'transactions' => $user->transactions()->latest()->get(),
            'totalInversion' => $totalInversion,
            'totalUtilidad' => $totalUtilidad,
            'totalGanancia' => $totalGanancia,
            'totalAvailable' => $totalAvailable,
        ]);
    }
}