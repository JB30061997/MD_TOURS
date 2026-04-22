<?php

namespace App\Http\Controllers;

use App\Models\TypeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypeServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TypeServices/Index', [
            'typeServices' => TypeService::latest()->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TypeServices/Create');
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

        $typeService = TypeService::create($data);

        return redirect()->back()->with([
            'success' => 'Type service ajouté avec succès',
            'lastCreatedTypeService' => $typeService
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // ما محتاجينهاش دابا
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('TypeServices/Edit', [
            'typeService' => TypeService::findOrFail($id)
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

        $typeService = TypeService::findOrFail($id);
        $typeService->update($data);

        return redirect()->route('type-services.index')
            ->with('success', 'Type service modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        TypeService::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Type service supprimé');
    }
}