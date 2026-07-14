<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = fn (Request $request): bool => $request->is('api') || $request->is('api/*') || $request->expectsJson();

        $apiError = fn (string $message, mixed $errors, int $code) => response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);

        $exceptions->shouldRenderJsonWhen(function (Request $request) use ($isApiRequest): bool {
            return $isApiRequest($request);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Unauthenticated.', null, 401);
        });

        $exceptions->render(function (UnauthorizedException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $message = count($e->getRequiredRoles()) > 0
                ? 'Access denied. This action requires the correct role.'
                : 'Access denied. You do not have permission to perform this action.';

            return $apiError($message, null, 403);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Access denied. You do not have permission to perform this action.', null, 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Access denied. You do not have permission to perform this action.', null, 403);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Validation failed.', $e->errors(), 422);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Resource not found.', null, 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($apiError, $isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return $apiError('Method not allowed.', null, 405);
        });
    })->create();
