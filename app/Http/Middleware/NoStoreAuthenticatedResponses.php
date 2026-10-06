<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoStoreAuthenticatedResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = (bool) $request->user();
        $response = $next($request);

        if ($authenticated || $request->routeIs('login', 'logout', 'dashboard', 'admin.*', 'supplier.*', 'driver.*', 'profile.*', 'website.quote.show')) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
