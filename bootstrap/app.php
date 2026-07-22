<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\MaintenanceMiddleware;
use App\Http\Middleware\PresidentMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PurchaserMiddleware;
use App\Http\Middleware\AccountingMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        commands: __DIR__.'/../routes/console.php',

        health: '/up'
    )
    ->withMiddleware(function ($middleware) {

        $middleware->alias([

            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'maintenance' => \App\Http\Middleware\MaintenanceMiddleware::class,
            'purchaser' => \App\Http\Middleware\PurchaserMiddleware::class,
            'president' => \App\Http\Middleware\PresidentMiddleware::class,

            'accounting' => \App\Http\Middleware\AccountingMiddleware::class,

            'receiving' => \App\Http\Middleware\ReceivingMiddleware::class,
        ]);

        

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
