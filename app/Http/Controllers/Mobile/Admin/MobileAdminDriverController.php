<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class MobileAdminDriverController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $drivers = Driver::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($drivers);
    }

    public function show(Driver $driver)
    {
        return response()->json([
            'driver' => $driver
        ]);
    }
}
