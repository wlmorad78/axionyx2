<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\ForceUtf8Response::class,
            \App\Http\Middleware\CompanyScope::class,
            \App\Http\Middleware\CompanyAccessMiddleware::class,
            \App\Http\Middleware\BranchScope::class,
        ]);
        $middleware->alias([
            'api.permission' => \App\Http\Middleware\CheckApiPermission::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'auto.permission' => \App\Http\Middleware\AutoPermission::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'plan.access' => \App\Http\Middleware\CheckPlanAccess::class,
            'day-closing' => \App\Http\Middleware\EnforceDayClosing::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
