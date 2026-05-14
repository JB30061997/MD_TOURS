<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SupplierClient;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $supplierClients = SupplierClient::with('user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupplierClients/Index', [
            'supplierClients' => $supplierClients,
            'allSupplierClients' => SupplierClient::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('SupplierClients/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        SupplierClient::create($request->only([
            'user_id',
            'name',
            'phone',
            'email',
            'address',
            'notes',
            'is_active',
        ]));

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur créé avec succès.');
    }

    public function edit($id)
    {
        return Inertia::render('SupplierClients/Edit', [
            'supplierClient' => SupplierClient::findOrFail($id),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplierClient = SupplierClient::findOrFail($id);

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $supplierClient->update($request->only([
            'user_id',
            'name',
            'phone',
            'email',
            'address',
            'notes',
            'is_active',
        ]));

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur modifié avec succès.');
    }

    public function replace(Request $request, SupplierClient $supplierClient)
    {
        $request->merge([
            'old_id' => $supplierClient->id,
        ]);

        $request->validate([
            'new_supplier_client_id' => 'required|exists:supplier_clients,id|different:old_id',
        ]);

        $updatedClients = Client::where('supplier_client_id', $supplierClient->id)
            ->update([
                'supplier_client_id' => $request->new_supplier_client_id,
            ]);

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', "Client supplier remplacé avec succès. {$updatedClients} client(s) mis à jour.");
    }

    public function destroy($id)
    {
        $supplierClient = SupplierClient::findOrFail($id);

        $linkedClientsCount = Client::where('supplier_client_id', $supplierClient->id)->count();

        if ($linkedClientsCount > 0) {
            return redirect()
                ->route('supplier-clients.index')
                ->with('error', "Impossible de supprimer ce supplier. Il est lié à {$linkedClientsCount} client(s). Veuillez d'abord le remplacer.");
        }

        $supplierClient->delete();

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur supprimé avec succès.');
    }
}
