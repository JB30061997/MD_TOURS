<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\TypeService;
use App\Models\Vehicule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_du')
            ? Carbon::parse($request->date_du)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->filled('date_au')
            ? Carbon::parse($request->date_au)->endOfDay()
            : now()->endOfMonth();

        $query = Planning::with([
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client',
        ])->whereBetween('date_du', [
            $dateFrom->toDateString(),
            $dateTo->toDateString(),
        ]);

        if ($request->filled('supplier_vehicule_id')) {
            $query->where('supplier_vehicule_id', $request->supplier_vehicule_id);
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }

        if ($request->filled('vehicule_id')) {
            $query->where('vehicule_id', $request->vehicule_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('guide_id')) {
            $query->where('guide_id', $request->guide_id);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('ref_dossier', 'like', "%{$search}%")
                    ->orWhere('flight', 'like', "%{$search}%")
                    ->orWhere('point_depart', 'like', "%{$search}%")
                    ->orWhere('site', 'like', "%{$search}%")
                    ->orWhereHas(
                        'supplierVehicule',
                        fn($sub) =>
                        $sub->where('name', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'driver',
                        fn($sub) =>
                        $sub->where('name', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'guide',
                        fn($sub) =>
                        $sub->where('name', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'service',
                        fn($sub) =>
                        $sub->where('designation', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'destination',
                        fn($sub) =>
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'vehicule',
                        fn($sub) =>
                        $sub->where('matricule', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'planningClients.client',
                        fn($sub) =>
                        $sub->where('full_name', 'like', "%{$search}%")
                    );
            });
        }

        $plannings = $query
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Plannings/Index', [
            'plannings' => $plannings,
            'supplierVehicules' => SupplierVehicule::orderBy('name')->get(),
            'supplierClients' => SupplierClient::orderBy('name')->get(),
            'drivers' => Driver::orderBy('name')->get(),
            'guides' => Guide::orderBy('name')->get(),
            'services' => Service::orderBy('designation')->get(),
            'clients' => Client::orderBy('full_name')->get(),
            'destinations' => Destination::orderBy('name')->get(),
            'vehicules' => Vehicule::orderBy('matricule')->get(),
            'filters' => [
                'date_du' => $dateFrom->toDateString(),
                'date_au' => $dateTo->toDateString(),
                'supplier_vehicule_id' => $request->supplier_vehicule_id ?? '',
                'driver_id' => $request->driver_id ?? '',
                'guide_id' => $request->guide_id ?? '',
                'service_id' => $request->service_id ?? '',
                'destination_id' => $request->destination_id ?? '',
                'vehicule_id' => $request->vehicule_id ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_du' => 'required|date',
            'ref_dossier' => 'required|string|max:255',
            'date_au' => 'nullable|date',
            'nbr_personnes' => 'nullable|integer',
            'flight' => 'nullable|string|max:255',
            'heure' => 'nullable',
            'point_depart' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:255',

            'service_id' => 'nullable|exists:services,id',
            'supplier_vehicule_id' => 'nullable|exists:supplier_vehicules,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'guide_id' => 'nullable|exists:guides,id',
            'destination_id' => 'nullable|exists:destinations,id',
            'vehicule_id' => 'nullable|exists:vehicules,id',

            'budget' => 'nullable|numeric',
            'supplier_price' => 'nullable|numeric',
            'notes' => 'nullable|string',

            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        DB::beginTransaction();

        try {
            $planning = Planning::create($data);

            foreach (($data['client_ids'] ?? []) as $clientId) {
                PlanningClient::firstOrCreate([
                    'planning_id' => $planning->id,
                    'client_id' => $clientId,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Planning ajouté avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur ajout planning : ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);

        $data = $request->validate([
            'date_du' => 'required|date',
            'ref_dossier' => 'required|string|max:255',
            'date_au' => 'nullable|date',
            'nbr_personnes' => 'nullable|integer',
            'flight' => 'nullable|string|max:255',
            'heure' => 'nullable',
            'point_depart' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:255',

            'service_id' => 'nullable|exists:services,id',
            'supplier_vehicule_id' => 'nullable|exists:supplier_vehicules,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'guide_id' => 'nullable|exists:guides,id',
            'destination_id' => 'nullable|exists:destinations,id',
            'vehicule_id' => 'nullable|exists:vehicules,id',

            'budget' => 'nullable|numeric',
            'supplier_price' => 'nullable|numeric',
            'notes' => 'nullable|string',

            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        DB::beginTransaction();

        try {
            $planning->update($data);

            PlanningClient::where('planning_id', $planning->id)->delete();

            foreach (($data['client_ids'] ?? []) as $clientId) {
                PlanningClient::firstOrCreate([
                    'planning_id' => $planning->id,
                    'client_id' => $clientId,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Planning modifié avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur modification planning : ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Planning::findOrFail($id)->delete();

            return redirect()->back()->with('success', 'Planning supprimé avec succès.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erreur suppression planning : ' . $e->getMessage());
        }
    }

    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls'],
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Fichier invalide : ' . $e->getMessage());
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());

            $defaultTypeService = TypeService::firstOrCreate([
                'designation' => 'Imported',
            ]);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $rows = $sheet->toArray(null, true, true, false);

                if (count($rows) < 2) {
                    continue;
                }

                $header = array_map(fn($v) => $this->normalizeHeader($v), $rows[0]);

                foreach (array_slice($rows, 1) as $index => $row) {
                    $line = $index + 2;

                    if ($this->rowIsEmpty($row)) {
                        $skipped++;
                        continue;
                    }

                    DB::beginTransaction();

                    try {
                        $data = $this->mapImportedRow($header, $row);

                        if (!$data['date_du'] || !$data['ref_dossier']) {
                            DB::rollBack();
                            $skipped++;
                            $errors[] = "Ligne {$line}: DU ou REF DOSSIER vide.";
                            continue;
                        }

                        $service = $this->firstOrCreateService($data['service_name'], $defaultTypeService);

                        // Excel Supliers => table supplier_clients
                        $supplierClient = $this->firstOrCreateSupplierClient($data['supplier_client_name']);

                        // Excel MD Driver => table supplier_vehicules
                        $supplierVehicule = $this->firstOrCreateSupplierVehicule($data['supplier_vehicule_name']);

                        $destination = $this->firstOrCreateDestination($data['destination_name']);
                        $vehicule = $this->firstOrCreateVehicule($data['vehicule_name']);
                        $guide = $this->firstOrCreateGuide($data['guide_name']);

                        $planning = Planning::where('ref_dossier', $data['ref_dossier'])
                            ->where('date_du', $data['date_du'])
                            ->where('flight', $data['flight'])
                            ->first();

                        $planningData = [
                            'date_du' => $data['date_du'],
                            'date_au' => $data['date_au'],
                            'ref_dossier' => $data['ref_dossier'],
                            'nbr_personnes' => $data['nbr_personnes'],
                            'flight' => $data['flight'],
                            'heure' => $data['heure'],
                            'point_depart' => $data['point_depart'],
                            'site' => $data['site'],
                            'service_id' => $service?->id,
                            'supplier_vehicule_id' => $supplierVehicule?->id,
                            'driver_id' => null,
                            'guide_id' => $guide?->id,
                            'destination_id' => $destination?->id,
                            'vehicule_id' => $vehicule?->id,
                            'budget' => $data['budget'],
                            'supplier_price' => $data['supplier_price'],
                            'notes' => 'Importé depuis Excel',
                        ];

                        if ($planning) {
                            $planning->update($planningData);
                            $updated++;
                        } else {
                            $planning = Planning::create($planningData);
                            $created++;
                        }

                        foreach ($this->extractClientNames($data['clients_name']) as $clientName) {
                            $client = $this->firstOrCreateClient($clientName, $supplierClient?->id);

                            PlanningClient::firstOrCreate([
                                'planning_id' => $planning->id,
                                'client_id' => $client->id,
                            ]);
                        }

                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();

                        $errors[] = "Ligne {$line}: " . $e->getMessage();
                        continue;
                    }
                }
            }

            return redirect()->route('plannings.index')->with([
                'success' => "Import terminé: {$created} créé(s), {$updated} modifié(s), {$skipped} ignoré(s), " . count($errors) . " erreur(s).",
                'import_errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erreur import Excel : ' . $e->getMessage());
        }
    }

    private function normalizeHeader($value): string
    {
        $value = trim((string) $value);
        $value = str_replace(["\n", "\r"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return mb_strtolower($value);
    }

    private function mapImportedRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {
            $mapped[$key] = $row[$index] ?? null;
        }

        return [
            'date_du' => $this->normalizeExcelDate($mapped['du'] ?? null),
            'date_au' => $this->normalizeExcelDate($mapped['au'] ?? null),
            'ref_dossier' => $this->cleanString($mapped['ref dossier'] ?? null),
            'vehicule_name' => $this->cleanString($mapped['bus'] ?? null),
            'nbr_personnes' => $this->toInteger($mapped['n/p'] ?? null),
            'service_name' => $this->cleanString($mapped['service'] ?? null),
            'flight' => $this->cleanString($mapped['flight'] ?? null),
            'heure' => $this->normalizeTime($mapped['heure'] ?? null),
            'point_depart' => $this->cleanString($mapped['point dép'] ?? $mapped['point départ'] ?? null),
            'destination_name' => $this->cleanString($mapped['destination'] ?? null),
            'site' => $this->cleanString($mapped['site'] ?? null),

            'supplier_client_name' => $this->cleanString($mapped['supliers'] ?? $mapped['supplier'] ?? null),
            'supplier_vehicule_name' => $this->cleanString($mapped['md driv'] ?? $mapped['md driver'] ?? null),

            'guide_name' => $this->cleanString($mapped['guide'] ?? null),
            'clients_name' => $mapped['clients name'] ?? null,
            'budget' => $this->toDecimal($mapped['budget'] ?? null),
            'supplier_price' => $this->toDecimal($mapped["supplier's price"] ?? null),
        ];
    }

    private function firstOrCreateSupplierClient(?string $name): ?SupplierClient
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        return SupplierClient::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => 'Créé automatiquement via import Excel',
                'is_active' => 1,
            ]
        );
    }

    private function firstOrCreateSupplierVehicule(?string $name): ?SupplierVehicule
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        return SupplierVehicule::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => 'Créé automatiquement via import Excel',
                'is_active' => 1,
            ]
        );
    }

    private function firstOrCreateDestination(?string $name): ?Destination
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        return Destination::firstOrCreate(
            ['name' => $name],
            [
                'city' => null,
                'country' => 'Maroc',
                'type' => null,
                'status' => 'Actif',
                'notes' => 'Créé automatiquement via import Excel',
            ]
        );
    }

    private function firstOrCreateVehicule(?string $matricule): ?Vehicule
    {
        $matricule = $this->cleanString($matricule);

        if (!$matricule) {
            return null;
        }

        return Vehicule::firstOrCreate(
            ['matricule' => $matricule],
            [
                'marque' => null,
                'modele' => null,
                'type' => null,
                'couleur' => null,
                'annee' => null,
                'nombre_places' => null,
                'carburant' => null,
                'boite_vitesse' => null,
                'status' => 'Disponible',
                'notes' => 'Créé automatiquement via import Excel',
            ]
        );
    }

    private function firstOrCreateService(?string $designation, TypeService $defaultTypeService): ?Service
    {
        $designation = $this->cleanString($designation);

        if (!$designation) {
            return null;
        }

        return Service::firstOrCreate(
            ['designation' => $designation],
            [
                'type_service' => $defaultTypeService->id,
            ]
        );
    }

    private function firstOrCreateGuide(?string $name): ?Guide
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        return Guide::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'status' => 'Disponible',
                'notes' => 'Créé automatiquement via import Excel',
            ]
        );
    }

    private function firstOrCreateClient(string $fullName, ?int $supplierClientId = null): Client
    {
        return Client::firstOrCreate(
            ['full_name' => $this->cleanString($fullName)],
            [
                'supplier_client_id' => $supplierClientId,
                'phone' => null,
                'email' => null,
                'notes' => 'Créé automatiquement via import Excel',
            ]
        );
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (!is_null($cell) && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private function normalizeExcelDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('H:i:s');
            }

            $value = trim((string) $value);
            $value = str_replace(['H', 'h'], ':', $value);

            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractClientNames($value): array
    {
        if (empty($value)) {
            return [];
        }

        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $parts = preg_split('/\n|,|;/', $text);

        return collect($parts)
            ->map(fn($item) => trim($item))
            ->filter(fn($item) => $item !== '')
            ->unique()
            ->values()
            ->toArray();
    }
}
