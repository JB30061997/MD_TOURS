<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SupplierClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $clients = Client::with('supplierClient')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('supplierClient', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create', [
            'supplierClients' => SupplierClient::select('id', 'name')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_client_id' => ['required', 'exists:supplier_clients,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Client ajouté avec succès.');
    }

    public function edit($id)
    {
        return Inertia::render('Clients/Edit', [
            'client' => Client::findOrFail($id),
            'supplierClients' => SupplierClient::select('id', 'name')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'supplier_client_id' => ['required', 'exists:supplier_clients,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Client::findOrFail($id)->update($data);

        return redirect()->route('clients.index')->with('success', 'Client modifié avec succès.');
    }

    public function destroy($id)
    {
        Client::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Client supprimé avec succès.');
    }
}
