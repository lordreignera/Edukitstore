<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoStoreAuthenticatedResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $wasAuthenticated = (bool) $request->user();
        $response = $next($request);

        return $this->apply($request, $response, $wasAuthenticated);
    }

    public function apply(Request $request, Response $response, bool $wasAuthenticated = false): Response
    {
        if ($wasAuthenticated || $request->user() || $request->routeIs(
            'login', 'logout', 'dashboard', 'password.*', 'two-factor.*', 'profile.*',
            'admin.*', 'finance.*', 'supplier.*', 'driver.*', 'school.*', 'parent.*',
            'website.quote.show'
        )) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        }

        return $response;
    }
}
