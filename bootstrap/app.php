<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureGoogleSession;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'user' => UserMiddleware::class,
            'admin' => AdminMiddleware::class,
            'auth.otp' => EnsureGoogleSession::class,
        ]);
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin*') ? route('admin.login') : route('user.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
