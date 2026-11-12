<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check dedicated per-portal guards first so Owner & Student can stay signed in simultaneously in one browser.
        foreach ($roles as $role) {
            if (in_array($role, ['owner', 'student'], true) && Auth::guard($role)->check()) {
                $user = Auth::guard($role)->user();
                if ($user && $user->role === $role) {
                    Auth::shouldUse($role);
                    $request->setUserResolver(fn () => $user);

                    return $next($request);
                }
            }
        }

        // Fallback to the default 'web' guard (supports test actingAs() and preserves role isolation).
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if (!in_array($user->role, $roles, true)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Unauthorized. Access denied for your role.'], 403);
                }
                abort(403, 'Unauthorized. Access denied for your role.');
            }

            Auth::shouldUse('web');
            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (in_array('student', $roles, true) && !in_array('owner', $roles, true)) {
            return redirect()->route('student.otp');
        }

        return redirect()->route('login');
    }
}
