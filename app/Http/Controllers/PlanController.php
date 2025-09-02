<?php

namespace App\Http\controllers;

use App\Http\Controllers\Traits\CreatesPaymentSchedules;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    use CreatesPaymentSchedules; // Usamos el Trait

    /**
     * Muestra la página de selección de plan al usuario.
     */
    public function selection()
    {
        return Inertia::render('Plan/Selection', [
            'plans' => Plan::all(),
        ]);
    }

    /**
     * Guarda la primera suscripción de un usuario.
     */
    public function store(Request $request)
    {
        // 1. Validación completa, incluyendo el comprobante.
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'amount' => 'required|numeric|min:200000',
            'receipt' => 'required|image|max:2048', // Obligatorio para usuarios nuevos
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $receiptPath = null;

        // 2. Lógica para guardar la imagen (asegúrate de que esto esté aquí)
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        DB::transaction(function () use ($user, $plan, $request, $receiptPath) {
            // 3. Creamos la suscripción con el path de la imagen
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'initial_investment' => $request->amount,
                'status' => 'pending_verification',
                'payment_receipt_path' => $receiptPath,
            ]);

            // 4. Llamamos a nuestro "ayudante" para crear los 6 pagos.
            $this->createPaymentSchedule($subscription);
        });

        return redirect()->route('dashboard')->with('success', '¡Gracias! Tu plan está en proceso de verificación.');
    }
}