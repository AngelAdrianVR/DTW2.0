<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

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
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // lógica para el error 404
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            return Inertia::render('404NotFound', [ // Nombre del componente a renderizar
                'status' => $e->getStatusCode(),
            ])->toResponse($request)->setStatusCode($e->getStatusCode());
        });

        // lógica para el error 419 (Sesión Expirada) y otros errores HTTP
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if (! app()->environment(['local', 'testing']) && $response->getStatusCode() === 419) {
                return Inertia::render('Errors/419', [
                    'status' => 419
                ])->toResponse($request)->setStatusCode(419);
            }
            return $response;
        });
    })->create();
