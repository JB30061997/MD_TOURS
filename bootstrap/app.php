<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\EnsureMobileUserActive;
use App\Http\Middleware\EnsureMobileAdminAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureRoutePermission::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'mobile.active' => EnsureMobileUserActive::class,
            'mobile.admin' => EnsureMobileAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, Request $request) {
            $isForbidden = $exception instanceof AuthorizationException
                || $exception instanceof UnauthorizedException
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403);

            if (!$isForbidden) {
                return null;
            }

            $message = 'Vous n’avez pas la permission d’effectuer cette action.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            if ($request->isMethod('GET')) {
                return redirect()
                    ->route('dashboard')
                    ->with('authorization_error', $message);
            }

            return back(303)->with('authorization_error', $message);
        });
    })->create();
