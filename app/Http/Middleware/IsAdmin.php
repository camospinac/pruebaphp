<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // <-- 1. AÑADE ESTA LÍNEA

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 👇 2. CAMBIA auth() POR Auth::
        if (Auth::check() && Auth::user()->rol === 'admin') {
            return $next($request);
        }

        abort(403, 'ACCESO NO AUTORIZADO.');
    }
}