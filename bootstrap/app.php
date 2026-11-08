<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (MissingAppKeyException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.deployment', ['issue' => 'app-key'], 503);
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.deployment', ['issue' => 'database'], 503);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
