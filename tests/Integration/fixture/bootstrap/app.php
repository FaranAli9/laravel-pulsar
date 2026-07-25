<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withEvents(discover: [
        app_path('Pulsar/Domain/*/Listeners'),
    ])
    ->withCommands([
        ...(glob(app_path('Pulsar/Services/*/Modules/*/Commands'), GLOB_ONLYDIR) ?: []),
    ])
    ->create();
