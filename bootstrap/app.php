<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackActivity::class,
            \App\Http\Middleware\EnsureAccountActive::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->encryptCookies(except: ['locale', 'theme', 'cookie_consent', 'vw_device', 'data_saver']);
        $middleware->alias([
            'permission' => \App\Http\Middleware\RequirePermission::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
