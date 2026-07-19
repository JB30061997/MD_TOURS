<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\MobileAdminAccess;

class MobileAuthController extends Controller
{
    public function login(Request $request, MobileAdminAccess $access)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $user = $request->user();

        if (! $user->active) {
            Auth::logout();

            return response()->json([
                'message' => 'Ce compte est désactivé.',
                'code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('mobile_app_token')->plainTextToken;

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->values()
            : collect([]);

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (bool) $user->active,
                'roles' => $roles,
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'mobile_access' => $access->payload($user),
                'driver' => \App\Models\Driver::where('user_id', $user->id)->first(),
                'guide' => \App\Models\Guide::where('user_id', $user->id)->first(),
                'supplier_client' => \App\Models\SupplierClient::where('user_id', $user->id)->first(),
                'supplier_vehicule' => \App\Models\SupplierVehicule::where('user_id', $user->id)->first(),
            ],
        ]);
    }

    public function me(Request $request, MobileAdminAccess $access)
    {
        $user = $request->user();

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->values()
            : collect([]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (bool) $user->active,
                'roles' => $roles,
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'mobile_access' => $access->payload($user),
                'driver' => \App\Models\Driver::where('user_id', $user->id)->first(),
                'guide' => \App\Models\Guide::where('user_id', $user->id)->first(),
                'supplier_client' => \App\Models\SupplierClient::where('user_id', $user->id)->first(),
                'supplier_vehicule' => \App\Models\SupplierVehicule::where('user_id', $user->id)->first(),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
