<?php

use App\Providers\MacroServiceProvider;
use App\Providers\PosServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        MacroServiceProvider::class,
        RepositoryServiceProvider::class,
        PosServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);

        $exceptions->render(function (HttpException $e) {
            return Response::error(
                message: $e->getMessage(),
                status: $e->getStatusCode(),
            );
        });

        $exceptions->render(function (ValidationException $e) {
            return Response::error(
                message: __('validation.failed', [], app()->getLocale()),
                status: 422,
                errors: $e->errors(),
            );
        });

        $exceptions->render(function (\ValueError $e) {
            return Response::error(
                message: $e->getMessage(),
                status: 422,
            );
        });
    })->create();
