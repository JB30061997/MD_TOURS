<?php

namespace App\Http\Controllers;

use App\Models\TypeSupplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypeSupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TypeSuppliers/Index', [
            'typeSuppliers' => TypeSupplier::latest()->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TypeSuppliers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $typeSupplier = TypeSupplier::create($data);

        return redirect()->back()->with([
            'success' => 'Type supplier ajouté avec succès',
            'lastCreatedTypeSupplier' => $typeSupplier
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('TypeSuppliers/Edit', [
            'typeSupplier' => TypeSupplier::findOrFail($id)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $typeSupplier = TypeSupplier::findOrFail($id);
        $typeSupplier->update($data);

        return redirect()->route('type-suppliers.index')
            ->with('success', 'Type supplier modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        TypeSupplier::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Type supplier supprimé');
    }
}
