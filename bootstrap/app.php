<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session token expired. Please refresh and try again.',
                ], 419);
            }

            $isStudentRoute = $request->is('student', 'student/*');
            if ($isStudentRoute) {
                $fallback = (auth('student')->check() || (auth('web')->check() && auth('web')->user()->isStudent()))
                    ? route('student.dashboard')
                    : route('student.otp');
            } else {
                $fallback = (auth('owner')->check() || (auth('web')->check() && auth('web')->user()->isOwner()))
                    ? route('owner.dashboard')
                    : route('login');
            }

            return redirect($request->headers->get('referer') ?: $fallback)
                ->with('error', 'Your session token refreshed. Please try your action again.');
        });
    })->create();
