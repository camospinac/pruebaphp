<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id_user',
        'tipo',
        'monto',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'observacion' => 'encrypted',
            'monto' => 'decimal:2',
        ];
    }

    /**
     * Define la relación inversa: Una transacción pertenece a un usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}