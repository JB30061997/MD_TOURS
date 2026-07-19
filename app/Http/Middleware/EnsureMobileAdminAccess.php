<?php

namespace App\Http\Middleware;

use App\Support\MobileAdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAdminAccess
{
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $user = $request->user();

        $access = app(MobileAdminAccess::class);
        $allowed = $user && $access->allowed($user);

        if ($allowed && $module) {
            $allowed = (bool) ($access->modules($user)[$module] ?? false);
        }

        abort_unless($allowed, 403);

        return $next($request);
    }
}
