<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\TypeSupplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $suppliers = Supplier::with('typeSupplier')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('typeSupplier', function ($typeQuery) use ($search) {
                            $typeQuery->where('designation', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create', [
            'typeSuppliers' => TypeSupplier::orderBy('designation')->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'exists:type_suppliers,id'],
            'phone'     => ['nullable', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:255'],
            'notes'     => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create($data);

        return redirect()->route('suppliers.index')->with([
            'success' => 'Supplier ajouté avec succès',
            'lastCreatedSupplier' => $supplier->load('typeSupplier')
        ]);
    }

    public function edit($id)
    {
        return Inertia::render('Suppliers/Edit', [
            'supplier' => Supplier::with('typeSupplier')->findOrFail($id),
            'typeSuppliers' => TypeSupplier::orderBy('designation')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'exists:type_suppliers,id'],
            'phone'     => ['nullable', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:255'],
            'notes'     => ['nullable', 'string'],
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier modifié avec succès');
    }

    public function destroy($id)
    {
        Supplier::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Supplier supprimé avec succès');
    }
}
