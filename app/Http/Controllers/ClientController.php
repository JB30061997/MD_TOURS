<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $clients = Client::query()
            ->when($search, function ($query, $search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'nullable',
            'email' => 'nullable',
            'notes' => 'nullable'
        ]);

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Client ajouté');
    }

    public function edit($id)
    {
        return Inertia::render('Clients/Edit', [
            'client' => Client::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'nullable',
            'email' => 'nullable',
            'notes' => 'nullable'
        ]);

        Client::findOrFail($id)->update($data);

        return redirect()->route('clients.index')->with('success', 'Client modifié');
    }

    public function destroy($id)
    {
        Client::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Client supprimé');
    }
}
