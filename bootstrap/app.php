<?php

use App\Http\Middleware\AppBasicAuth;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [\App\Http\Middleware\SanitizeApiKey::class], append: [SecurityHeaders::class]);
        $middleware->alias(['app_basic_auth' => AppBasicAuth::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
