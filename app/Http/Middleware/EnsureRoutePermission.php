<?php

namespace App\Http\Middleware;

use App\Support\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (!$user || !$routeName || $this->isPublicAuthenticatedRoute($routeName)) {
            return $next($request);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $permission = $this->permissionForRoute($routeName);

        if ($permission && in_array($permission, PermissionRegistry::permissions(), true)) {
            if (!$user->can($permission)) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    private function permissionForRoute(string $routeName): ?string
    {
        $parts = explode('.', $routeName);
        $module = $parts[0] ?? null;
        $tail = implode('.', array_slice($parts, 1));
        $last = end($parts) ?: '';

        if (!$module) {
            return null;
        }

        $action = match (true) {
            $module === 'roles-permissions' && $last === 'index' => 'view',
            $module === 'roles-permissions' => 'manage',
            in_array($last, ['index', 'show', 'report', 'demo', 'by-invoice'], true) => 'view',
            in_array($last, ['create', 'store'], true) => 'create',
            in_array($last, ['edit', 'update', 'reorder', 'toggle-status', 'release'], true) => 'edit',
            in_array($last, ['destroy', 'reject'], true) => 'delete',
            str_contains($tail, 'validate') => 'validate',
            str_contains($tail, 'import') => 'import',
            str_contains($tail, 'export') => 'export',
            str_contains($tail, 'print') || str_contains($tail, 'pdf') => 'print',
            str_contains($tail, 'email') || str_contains($tail, 'sync') => 'email',
            str_contains($tail, 'payment')
                || str_contains($tail, 'replace')
                || str_contains($tail, 'matrix')
                || str_contains($tail, 'quick')
                || str_contains($tail, 'tarifs')
                || str_contains($tail, 'supplier')
                || str_contains($tail, 'vehicle-assignment')
                || str_contains($tail, 'fuel-card')
                => 'manage',
            default => null,
        };

        return $action ? "{$module}.{$action}" : null;
    }

    private function deny(Request $request): RedirectResponse|JsonResponse
    {
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
    }

    private function isPublicAuthenticatedRoute(string $routeName): bool
    {
        return str_starts_with($routeName, 'profile.')
            || $routeName === 'dashboard'
            || str_starts_with($routeName, 'logout')
            || str_starts_with($routeName, 'verification.')
            || str_starts_with($routeName, 'password.');
    }
}
