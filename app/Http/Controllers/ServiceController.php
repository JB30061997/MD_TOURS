<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TypeService;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $hasDescriptionColumn = Schema::hasColumn('services', 'description');

        $services = Service::with('typeService')
            ->when($search, function ($query, $search) use ($hasDescriptionColumn) {
                $query->where(function ($q) use ($search, $hasDescriptionColumn) {
                    $q->where('designation', 'like', "%{$search}%");

                    if ($hasDescriptionColumn) {
                        $q->orWhere('description', 'like', "%{$search}%");
                    }

                    $q->orWhereHas('typeService', function ($typeQuery) use ($search) {
                        $typeQuery->where('designation', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $services->getCollection()->transform(fn (Service $service) => [
            'id' => $service->id,
            'designation' => $service->designation,
            'type_service_id' => $service->type_service,
            'type_service_name' => $service->typeService?->designation,
            'description' => $hasDescriptionColumn ? $service->description : null,
        ]);

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $search
            ],
            'allServices' => Service::orderBy('designation')->get(['id', 'designation']),
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
        $rules = [
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
        ];

        if (Schema::hasColumn('services', 'description')) {
            $rules['description'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        $service = Service::create($data);

        return redirect()->route('services.index')->with([
            'success' => 'Service ajouté avec succès',
            'lastCreatedService' => $service->load('typeService'),
        ]);
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        $hasDescriptionColumn = Schema::hasColumn('services', 'description');

        return Inertia::render('Services/Edit', [
            'service' => [
                'id' => $service->id,
                'designation' => $service->designation,
                'type_service' => $service->type_service,
                'description' => $hasDescriptionColumn ? $service->description : null,
            ],
            'typeServices' => TypeService::orderBy('designation')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'designation'   => ['required', 'string', 'max:255'],
            'type_service'  => ['required', 'exists:type_services,id'],
        ];

        if (Schema::hasColumn('services', 'description')) {
            $rules['description'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        $service = Service::findOrFail($id);
        $service->update($data);

        return redirect()->route('services.index')
            ->with('success', 'Service modifié avec succès');
    }

    public function bulkReplace(Request $request)
    {
        $data = $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'replacement_service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $replacementId = (int) $data['replacement_service_id'];
        $serviceIds = collect($data['service_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $replacementId)
            ->values();

        if ($serviceIds->isEmpty()) {
            return redirect()->back()->with('error', 'Choisissez au moins un service différent du service de remplacement.');
        }

        try {
            $summary = DB::transaction(function () use ($serviceIds, $replacementId) {
                $ids = $serviceIds->all();

                $planningCount = DB::table('plannings')
                    ->whereIn('service_id', $ids)
                    ->update(['service_id' => $replacementId]);

                $commandeCount = Schema::hasTable('commandes')
                    ? DB::table('commandes')->whereIn('service_id', $ids)->update(['service_id' => $replacementId])
                    : 0;

                $tarifCount = 0;

                if (Schema::hasTable('supplier_vehicule_service_tarifs')) {
                    $tarifs = DB::table('supplier_vehicule_service_tarifs')
                        ->whereIn('service_id', $ids)
                        ->get();

                    foreach ($tarifs as $tarif) {
                        $duplicate = DB::table('supplier_vehicule_service_tarifs')
                            ->where('supplier_vehicule_id', $tarif->supplier_vehicule_id)
                            ->where('service_id', $replacementId)
                            ->where('type_service_id', $tarif->type_service_id)
                            ->where('vehicle_seats', $tarif->vehicle_seats)
                            ->exists();

                        if ($duplicate) {
                            DB::table('supplier_vehicule_service_tarifs')->where('id', $tarif->id)->delete();
                        } else {
                            DB::table('supplier_vehicule_service_tarifs')->where('id', $tarif->id)->update([
                                'service_id' => $replacementId,
                                'updated_at' => now(),
                            ]);
                        }

                        $tarifCount++;
                    }
                }

                $deletedCount = Service::whereIn('id', $ids)->delete();

                return compact('planningCount', 'commandeCount', 'tarifCount', 'deletedCount');
            });
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ces services', $exception));
        }

        return redirect()->back()->with(
            'success',
            "Remplacement terminé: {$summary['deletedCount']} service(s), {$summary['planningCount']} planning(s), {$summary['commandeCount']} bon(s), {$summary['tarifCount']} tarif(s)."
        );
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $message = DeleteProtection::blockingMessage('ce service', [
            ['table' => 'plannings', 'column' => 'service_id', 'value' => $service->id, 'label' => 'planning(s)'],
            ['table' => 'commandes', 'column' => 'service_id', 'value' => $service->id, 'label' => 'bon(s) de commande'],
            ['table' => 'supplier_vehicule_service_tarifs', 'column' => 'service_id', 'value' => $service->id, 'label' => 'tarif(s) fournisseur'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $service->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ce service', $exception));
        }

        return redirect()->back()
            ->with('success', 'Service supprimé avec succès');
    }
}
