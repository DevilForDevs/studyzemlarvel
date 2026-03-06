<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'signup',         // exclude /signup
            'login',          // exclude /login
            "logout",
            "update-password",
            "delete-account",
            "sendOtp",
            "verify-otp",
            "createIvdCoupan",
            "create-order",
            "verifyPayment"
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
