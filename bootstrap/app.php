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
        $handleSessionExpired = function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please try again.',
                ], 419);
            }

            $fallback = $request->is('student', 'student/*')
                ? route('student.otp')
                : route('login');

            return redirect($fallback)
                ->with('error', 'Your session timed out. Please sign in now.');
        };

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) use ($handleSessionExpired) {
            if ($e->getStatusCode() === 419) {
                return $handleSessionExpired($request);
            }
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($handleSessionExpired) {
            return $handleSessionExpired($request);
        });
    })->create();
