<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CreatesPaymentSchedules; // 1. Importa el Trait
use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    use CreatesPaymentSchedules; // 2. Usa el Trait

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Lógica para calcular el saldo disponible (esto está perfecto)
        $abonos = $user->transactions()->where('tipo', 'abono')->sum('monto');
        $retiros = $user->transactions()->where('tipo', 'retiro')->sum('monto');
        $totalAvailable = $abonos - $retiros;

        // Lógica de validación (esto está perfecto)
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|in:transfer,balance',
            'receipt' => [Rule::requiredIf($request->payment_method === 'transfer'), 'image', 'max:2048'],
            'amount' => [
                'required', 'numeric', 'min:200000',
                function ($attribute, $value, $fail) use ($request, $totalAvailable) {
                    if ($request->payment_method === 'balance' && $value > $totalAvailable) {
                        $fail('El monto a invertir no puede ser mayor a tu saldo disponible.');
                    }
                },
            ],
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        DB::transaction(function () use ($user, $plan, $request) {
            $receiptPath = null;
            $status = '';

            // Lógica para el método de pago (esto está perfecto)
            if ($request->payment_method === 'balance') {
                $status = 'active';
                Transaction::create([
                    'id_user' => $user->id,
                    'tipo' => 'retiro',
                    'monto' => $request->amount,
                    'observacion' => "Reinvertido en nuevo plan: {$plan->name}",
                ]);
            } else {
                $status = 'pending_verification';
                if ($request->hasFile('receipt')) {
                    $receiptPath = $request->file('receipt')->store('receipts', 'public');
                }
            }

            // Creamos la nueva suscripción
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'initial_investment' => $request->amount,
                'status' => $status,
                'payment_receipt_path' => $receiptPath,
            ]);

            // 3. ¡AQUÍ ESTÁ LA MAGIA!
            // En lugar de todo el código duplicado, solo llamamos a nuestro "ayudante".
            $this->createPaymentSchedule($subscription);
        });

        return redirect()->route('dashboard')->with('success', '¡Tu nueva inversión ha sido creada con éxito!');
    }
}