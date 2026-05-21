<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SupplierClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAdminSupplierClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $supplierClients = SupplierClient::query()
            ->with('user:id,name,email')
            ->withCount('clients')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return response()->json($supplierClients);
    }

    public function show(SupplierClient $supplierClient)
    {
        $supplierClient->load('user:id,name,email');

        $linkedClientsCount = Client::where('supplier_client_id', $supplierClient->id)
            ->count();

        $linkedClients = Client::where('supplier_client_id', $supplierClient->id)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'supplier_client' => $supplierClient,
            'linked_clients_count' => $linkedClientsCount,
            'linked_clients' => $linkedClients,
        ]);
    }

    public function replaceOptions(SupplierClient $supplierClient)
    {
        $options = SupplierClient::query()
            ->where('id', '!=', $supplierClient->id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);

        $linkedClientsCount = Client::where('supplier_client_id', $supplierClient->id)
            ->count();

        return response()->json([
            'supplier_client' => $supplierClient,
            'linked_clients_count' => $linkedClientsCount,
            'replacement_options' => $options,
        ]);
    }

    public function replace(Request $request, SupplierClient $supplierClient)
    {
        $validated = $request->validate([
            'new_supplier_client_id' => [
                'required',
                'exists:supplier_clients,id',
                'different:old_id',
            ],
        ]);

        if ((int) $validated['new_supplier_client_id'] === (int) $supplierClient->id) {
            return response()->json([
                'message' => 'Le nouveau supplier client doit être différent.',
            ], 422);
        }

        $updatedClients = DB::transaction(function () use ($supplierClient, $validated) {
            return Client::where('supplier_client_id', $supplierClient->id)
                ->update([
                    'supplier_client_id' => $validated['new_supplier_client_id'],
                ]);
        });

        return response()->json([
            'message' => 'Client supplier remplacé avec succès.',
            'updated_clients' => $updatedClients,
        ]);
    }
}
