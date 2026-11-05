<?php

namespace App\Http\Middleware;

use App\Support\DemoAccountAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAccountIsAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionUser = $request->user('web');
        $user = $sessionUser?->fresh();

        if ($sessionUser && (! $user || ! $user->is_active || $user->hasRole('parent') || (
            ! DemoAccountAccess::isAllowed($user->email)
        ))) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'This account is not available. Please contact an administrator.');
        }

        return $next($request);
    }
}
