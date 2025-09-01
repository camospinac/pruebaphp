<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // 1. Calculamos el saldo disponible del usuario
        $abonos = $user->transactions()->where('tipo', 'abono')->sum('monto');
        $retiros = $user->transactions()->where('tipo', 'retiro')->sum('monto');
        $totalAvailable = $abonos - $retiros;

        // 2. Validamos que el monto sea válido y no exceda el saldo
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', "max:{$totalAvailable}"],
        ]);

        $newCode = null;

        DB::transaction(function () use ($user, $request, &$newCode) {
            // 3. Generamos un código único de 6 dígitos
            do {
                $newCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (Withdrawal::where('code', $newCode)->exists());

            // 4. Creamos la solicitud de retiro
            Withdrawal::create([
                'user_id' => $user->id,
                'code' => $newCode,
                'amount' => $request->amount,
                'status' => 'pending',
            ]);

            // 5. Creamos la transacción de 'retiro' para descontar el saldo inmediatamente
            Transaction::create([
                'id_user' => $user->id,
                'tipo' => 'retiro',
                'monto' => $request->amount,
                'observacion' => "Solicitud de retiro con código: {$newCode}",
            ]);
        });

        // 6. Enviamos el código a la vista a través de un mensaje flash
        return redirect()->route('dashboard')
            ->with('success', '¡Solicitud de retiro generada con éxito!')
            ->with('withdrawal_code', $newCode);
    }
}