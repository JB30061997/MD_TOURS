<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileDeviceToken;
use Illuminate\Http\Request;

class MobileDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'max:30'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:80'],
        ]);

        $token = MobileDeviceToken::updateOrCreate(
            ['token_hash' => MobileDeviceToken::hashToken($data['token'])],
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
                'platform' => $data['platform'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'device_token_id' => $token->id,
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:4096'],
        ]);

        $query = MobileDeviceToken::where('user_id', $request->user()->id);

        if (!empty($data['token'])) {
            $query->where('token_hash', MobileDeviceToken::hashToken($data['token']));
        }

        $deleted = $query->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }
}
