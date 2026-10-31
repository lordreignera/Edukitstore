<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAccountIsAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionUser = $request->user('web');
        $user = $sessionUser?->fresh();

        if ($sessionUser && (! $user || ! $user->is_active || (
            ! app()->environment(['local', 'testing'])
            && Str::endsWith(Str::lower($user->email), '@edukit.test')
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
