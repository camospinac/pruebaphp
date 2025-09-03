<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // --- CÁLCULOS PARA LAS TARJETAS DE RESUMEN ---
        $totalUsers = User::where('rol', 'usuario')->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $pendingSubscriptions = Subscription::where('status', 'pending_verification')->count();
        $pendingWithdrawalsValue = Withdrawal::where('status', 'pending')->sum('amount');

        // --- CÁLCULO PARA EL LOG DE ACTIVIDAD RECIENTE ---
        // Obtenemos las últimas 5 suscripciones y los últimos 5 retiros
        $recentSubscriptions = Subscription::with('user')->latest()->take(5)->get();
        $recentWithdrawals = Withdrawal::with('user')->latest()->take(5)->get();

        // Los unimos en una sola colección y los ordenamos por fecha de creación
        $recentActivity = $recentSubscriptions->concat($recentWithdrawals)
            ->sortByDesc('created_at')
            ->take(10) // Tomamos los 10 más recientes de la mezcla
            ->values() // Resetea los índices del array
            ->map(function ($item) {
                // Damos un formato unificado a cada item
                return [
                    'type' => $item instanceof Subscription ? 'Suscripción' : 'Retiro',
                    'user_name' => $item->user->nombres ?? 'Usuario Desconocido',
                    'amount' => $item instanceof Subscription ? $item->initial_investment : $item->amount,
                    'status' => $item->status,
                    'date' => $item->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeSubscriptions' => $activeSubscriptions,
                'pendingSubscriptions' => $pendingSubscriptions,
                'pendingWithdrawalsValue' => $pendingWithdrawalsValue,
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}