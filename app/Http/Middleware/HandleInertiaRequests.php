<?php

namespace App\Http\Middleware;

use App\Models\ReservationDraft;
use App\Support\PermissionRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => fn () => $user && method_exists($user, 'getRoleNames')
                    ? $user->getRoleNames()->values()
                    : [],
                'permissions' => fn () => $user && method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()->pluck('name')->values()
                    : [],
                'permissionGroups' => fn () => PermissionRegistry::grouped(),
                'isSuperAdmin' => fn () => $user && method_exists($user, 'isSuperAdmin')
                    ? $user->isSuperAdmin()
                    : false,
                'can' => fn () => $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                    ? collect(PermissionRegistry::permissions())->mapWithKeys(fn ($permission) => [$permission => true])
                    : collect(PermissionRegistry::permissions())->mapWithKeys(fn ($permission) => [
                        $permission => $user ? $user->can($permission) : false,
                    ]),
            ],
            'reservationDrafts' => [
                'pending' => fn () => $request->user()
                    ? ReservationDraft::where('status', 'pending')->count()
                    : 0,
            ],
        ];
    }
}
