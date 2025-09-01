<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanIsSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $hasActiveSubscription = $user->subscriptions()->where('status', 'active')->exists();
        if ($hasActiveSubscription || $request->routeIs('plan.selection') || $request->routeIs('plan.store')) {
            return $next($request);
        }
        return redirect()->route('plan.selection');
    }
}
