<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use App\Support\MobileAdminAccess;
use Illuminate\Http\Request;

class MobileAdminDashboardController extends Controller
{
    public function index(Request $request, AdminDashboardService $dashboard, MobileAdminAccess $access)
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'in:today,week,month,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $payload = $dashboard->snapshot(
            $validated['filter'] ?? 'month',
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        return response()->json($payload + [
            'access' => $access->payload($request->user()),
        ]);
    }
}
