<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
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
        // Route-model binding miss (e.g. `Todo $todo` resolved against an id
        // that doesn't exist). Converts the model class basename into a
        // friendly message like "Todo not found."
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $resource = class_basename($e->getModel() ?: 'Resource');

            return response()->json([
                'success' => false,
                'message' => "{$resource} not found.",
                'data' => null,
            ], 404);
        });

        // Catches both the converted ModelNotFoundException (if not matched
        // above) and genuine "no such route" 404s.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'data' => null,
            ], 404);
        });

        // FormRequest::authorize() returning false (non-owner attempting
        // view/update/delete on a ToDo).
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'This action is unauthorized.',
                'data' => null,
            ], 403);
        });
    })->create();
