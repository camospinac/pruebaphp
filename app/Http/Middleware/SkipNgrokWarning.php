<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipNgrokWarning
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // First, get the response that the application is about to send
        $response = $next($request);

        // Then, add the special ngrok header to it
        $response->headers->set('ngrok-skip-browser-warning', 'true');

        // Finally, return the modified response
        return $response;
    }
}