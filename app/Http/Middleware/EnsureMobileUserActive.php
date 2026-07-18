<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->active) {
            $request->user()?->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Ce compte est désactivé.',
                'code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        return $next($request);
    }
}
