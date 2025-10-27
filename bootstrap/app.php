<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            if (!$request->expectsJson()) return;
            $status = 404;
            return response()->json([
                'error' => [
                    'status'  => $status,
                    'code'    => 'resource_not_found',
                    'message' => 'Requested resource was not found.'
                ],
            ], $status);
        });

        $exceptions->render(function (ValidationException $e, $request) {
            if (!$request->expectsJson()) return;
            $status = 422;
            return response()->json([
                'error' => [
                    'status'  => $status,
                    'code'    => 'validation_error',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors()
                ],
            ], $status);
        });

        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if (!$request->expectsJson()) return;
            $status = $e->getStatusCode();
            $code = match ($status) {
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'forbidden',
                409 => 'conflict',
                429 => 'too_many_requests',
                default => 'http_error'
            };
            return response()->json([
                'error' => [
                    'status'  => $status,
                    'code'    => $code,
                    'message' => $e->getMessage() ?: 'HTTP error.'
                ],
            ], $status);
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->expectsJson()) return;
            $status = 500;
            return response()->json([
                'error' => [
                    'status'  => $status,
                    'code'    => 'internal_server_error',
                    'message' => app()->hasDebugModeEnabled()
                        ? $e->getMessage()
                        : 'Something went wrong.'
                ],
            ], $status);
        });
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->create();
