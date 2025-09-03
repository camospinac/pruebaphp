<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Rank;
use App\Models\Transaction;

class UserObserver
{
    /**
     * Se ejecuta DESPUÉS de que un usuario ha sido creado.
     */
    public function created(User $user): void
    {
        // Si el usuario no fue referido por nadie, no hacemos nada.
        if (! $user->referred_by_id) {
            return;
        }

        // Buscamos al usuario que lo refirió (el "padrino")
        $referrer = User::find($user->referred_by_id);

        if ($referrer) {
            // Incrementamos su contador de referidos en 1
            $referrer->increment('referral_count');

            // Obtenemos el nuevo total de referidos
            $newCount = $referrer->referral_count;

            // Buscamos si existe un nuevo rango que coincida con el nuevo total de referidos
            $newRank = Rank::where('required_referrals', $newCount)->where('is_active', true)->first();

            // ¡Premio! Si se encontró un nuevo rango...
            if ($newRank) {
                // 1. Le asignamos el nuevo rango al padrino
                $referrer->rank_id = $newRank->id;
                $referrer->save();

                // 2. Creamos la transacción de 'abono' como recompensa
                Transaction::create([
                    'id_user' => $referrer->id,
                    'tipo' => 'abono',
                    'monto' => $newRank->reward_amount,
                    'observacion' => "Recompensa por alcanzar el rango: {$newRank->name}",
                ]);
            }
        }
    }
}