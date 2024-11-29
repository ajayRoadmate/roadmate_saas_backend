<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Services\ExceptionHandlerService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v3')
                ->group(base_path('routes/executive_api.php'));

            Route::middleware('api')
                ->prefix('api/v3')
                ->group(base_path('routes/partner_api.php'));

            Route::middleware('api')
                ->prefix('api/v3')
                ->group(base_path('routes/customer_api.php'));

            Route::middleware('api')
            ->prefix('api/admin/')
            ->group(base_path('routes/admin_api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ExceptionHandlerService::handleExceptions($exceptions);
    })->create();
