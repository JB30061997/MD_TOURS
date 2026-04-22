<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TypeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $services = Service::with('typeService')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('designation', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('typeService', function ($typeQuery) use ($search) {
                            $typeQuery->where('designation', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Services/Create', [
            'typeServices' => TypeService::orderBy('designation')->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
            'description'   => ['nullable', 'string'],
        ]);

        $service = Service::create($data);

        return redirect()->route('services.index')->with([
            'success' => 'Service ajouté avec succès',
            'lastCreatedService' => $service->load('typeService'),
        ]);
    }

    public function edit($id)
    {
        return Inertia::render('Services/Edit', [
            'service' => Service::with('typeService')->findOrFail($id),
            'typeServices' => TypeService::orderBy('designation')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
            'description'   => ['nullable', 'string'],
        ]);

        $service = Service::findOrFail($id);
        $service->update($data);

        return redirect()->route('services.index')
            ->with('success', 'Service modifié avec succès');
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Service supprimé avec succès');
    }
}
