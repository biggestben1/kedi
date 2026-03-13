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
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'restrict.wholesale_staff_admin' => \App\Http\Middleware\RestrictWholesaleStaffAdmin::class,
            'restrict.reseller_admin' => \App\Http\Middleware\RestrictResellerAdmin::class,
            'restrict.dispatch_admin' => \App\Http\Middleware\RestrictDispatchAdmin::class,
            'restrict.headquarters_admin' => \App\Http\Middleware\RestrictHeadquartersAdmin::class,
            'restrict.branch_admin' => \App\Http\Middleware\RestrictBranchAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
