<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpireIdleWebSessions
{
    private const ACTIVITY_KEY = 'security.last_user_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user) {
            return $next($request);
        }

        $now = now()->timestamp;
        $timeoutSeconds = max(60, (int) config('session.idle_timeout_minutes', 5) * 60);
        $lastActivity = (int) $request->session()->get(self::ACTIVITY_KEY, $now);
        $effectiveActivity = $now;

        if ($request->routeIs('session.heartbeat')) {
            $activityAge = filter_var($request->input('activity_age_ms'), FILTER_VALIDATE_INT);

            // A heartbeat is only a keepalive, not proof of user activity. Preserve
            // the event time reported by the browser instead of extending idle time
            // to the time the background heartbeat happened.
            if ($activityAge !== false && $activityAge >= 0 && $activityAge <= $timeoutSeconds * 1000) {
                $reportedActivity = $now - (int) ceil($activityAge / 1000);
                $effectiveActivity = $reportedActivity > $lastActivity ? $reportedActivity : $lastActivity;
            } else {
                $effectiveActivity = $lastActivity;
            }
        }

        $idleDuration = $request->routeIs('session.heartbeat')
            ? ($effectiveActivity > $lastActivity ? $effectiveActivity - $lastActivity : $now - $lastActivity)
            : $now - $lastActivity;

        if ($idleDuration >= $timeoutSeconds) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->routeIs('session.heartbeat')) {
                return response()->json(['message' => 'Your session ended after inactivity. Please sign in again.'], 401);
            }

            $idleMinutes = (int) config('session.idle_timeout_minutes', 5);

            return redirect()->route('login')
                ->with('status', "You were signed out after {$idleMinutes} minutes of inactivity. Please sign in again.");
        }

        if ($request->routeIs('session.heartbeat')) {
            if ($effectiveActivity > $lastActivity) {
                $request->session()->put(self::ACTIVITY_KEY, $effectiveActivity);
            }
        } else {
            $request->session()->put(self::ACTIVITY_KEY, $now);
        }

        return $next($request);
    }
}
