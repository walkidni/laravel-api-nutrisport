<?php

use App\Http\Middleware\ResolveCurrentSite;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v1',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:backoffice']],
    )
    ->withSchedule(function (Schedule $schedule): void {
        //
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'resolve.current.site' => ResolveCurrentSite::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
