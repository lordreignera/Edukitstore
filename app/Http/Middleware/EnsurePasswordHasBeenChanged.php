<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordHasBeenChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('profile.show')) {
            return redirect()->route('profile.show')
                ->with('status', 'Please change your temporary password before continuing.');
        }

        return $next($request);
    }
}
