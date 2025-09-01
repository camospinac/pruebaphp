<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CreatesPaymentSchedules;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Cmixin\BusinessDay;

class PlanController extends Controller
{
    
    /**
     * Muestra la página de selección de plan al usuario.
     *
     * @return \Inertia\Response
     */
    use CreatesPaymentSchedules; 
    public function selection()
    {
        return Inertia::render('Plan/Selection', [
            'plans' => Plan::all(),
        ]);
    }

    /**
     * Guarda la suscripción del usuario a un plan y genera los pagos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'amount' => 'required|numeric|min:1',
            'receipt' => 'required|image|max:2048',
        ]);

        BusinessDay::enable('Carbon\Carbon', 'es_CO');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $receiptPath = null;

        if ($request->hasFile('receipt')) {
        $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        DB::transaction(function () use ($user, $plan, $request, $receiptPath) {
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'initial_investment' => $request->amount,
                'status' => 'active',
                'payment_receipt_path' => $receiptPath,
            ]);
            $this->createPaymentSchedule($subscription);
        });

        return redirect()->route('dashboard')->with('success', '¡Plan activado y pagos generados con éxito!');
    }
}