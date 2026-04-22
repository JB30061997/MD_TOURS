<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Planning;
use App\Models\Supplier;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\PlanningClient;
use App\Models\Service;
use App\Models\TypeService;
use App\Models\TypeSupplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

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
            'supplier',
            'driver',
            'guide',
            'service',
            'planningClients.client'
        ])->whereBetween('date_du', [
            $dateFrom->toDateString(),
            $dateTo->toDateString()
        ]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
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
                    ->orWhere('bus', 'like', "%{$search}%")
                    ->orWhere('flight', 'like', "%{$search}%")
                    ->orWhere('point_depart', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guide', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function ($sub) use ($search) {
                        $sub->where('designation', 'like', "%{$search}%");
                    })
                    ->orWhereHas('planningClients.client', function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $plannings = $query
            ->orderByDesc('date_du')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Plannings/Index', [
            'plannings' => $plannings,
            'suppliers' => Supplier::orderBy('name')->get(),
            'drivers' => Driver::orderBy('name')->get(),
            'guides' => Guide::orderBy('name')->get(),
            'services' => Service::orderBy('designation')->get(),
            'clients' => Client::orderBy('full_name')->get(),
            'supplierTypes' => TypeSupplier::orderBy('designation')->get(),
            'filters' => [
                'date_du' => $dateFrom->toDateString(),
                'date_au' => $dateTo->toDateString(),
                'supplier_id' => $request->supplier_id ?? '',
                'driver_id' => $request->driver_id ?? '',
                'guide_id' => $request->guide_id ?? '',
                'service_id' => $request->service_id ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_du' => 'required|date',
            'ref_dossier' => 'required|string|max:255',
            'destination' => 'required|string|max:255',

            'date_au' => 'nullable|date',
            'bus' => 'nullable|string|max:255',
            'nbr_personnes' => 'nullable|integer',
            'flight' => 'nullable|string|max:255',
            'heure' => 'nullable',
            'point_depart' => 'nullable|string|max:255',

            'supplier_id' => 'nullable|exists:suppliers,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'guide_id' => 'nullable|exists:guides,id',
            'service_id' => 'nullable|exists:services,id',

            'budget' => 'nullable|numeric',
            'supplier_price' => 'nullable|numeric',
            'notes' => 'nullable|string',

            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        DB::beginTransaction();

        try {
            $planning = Planning::create($data);

            if (!empty($data['client_ids'])) {
                foreach ($data['client_ids'] as $clientId) {
                    PlanningClient::firstOrCreate([
                        'planning_id' => $planning->id,
                        'client_id' => $clientId,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Planning ajouté avec succès');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur ajout planning : ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $planning = Planning::with([
            'supplier',
            'driver',
            'guide',
            'service',
            'planningClients.client'
        ])->findOrFail($id);

        return Inertia::render('Plannings/Edit', [
            'planning' => $planning,
            'suppliers' => Supplier::orderBy('name')->get(),
            'drivers' => Driver::orderBy('name')->get(),
            'guides' => Guide::orderBy('name')->get(),
            'services' => Service::orderBy('designation')->get(),
            'clients' => Client::orderBy('full_name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);

        $data = $request->validate([
            'date_du' => 'required|date',
            'ref_dossier' => 'required|string|max:255',
            'destination' => 'required|string|max:255',

            'date_au' => 'nullable|date',
            'bus' => 'nullable|string|max:255',
            'nbr_personnes' => 'nullable|integer',
            'flight' => 'nullable|string|max:255',
            'heure' => 'nullable',
            'point_depart' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:255',

            'supplier_id' => 'nullable|exists:suppliers,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'guide_id' => 'nullable|exists:guides,id',
            'service_id' => 'nullable|exists:services,id',

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

            if (!empty($data['client_ids'])) {
                foreach ($data['client_ids'] as $clientId) {
                    PlanningClient::firstOrCreate([
                        'planning_id' => $planning->id,
                        'client_id' => $clientId,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('plannings.index')->with('success', 'Planning modifié');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur modification planning : ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        Planning::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Planning supprimé');
    }

    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls']
        ]);

        $file = $request->file('file');

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errorsList = [];

        try {
            $spreadsheet = IOFactory::load($file->getPathname());

            $defaultTypeService = TypeService::firstOrCreate(
                ['designation' => 'Imported']
            );

            $defaultTypeSupplier = TypeSupplier::firstOrCreate(
                ['designation' => 'Imported']
            );

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $rows = $worksheet->toArray(null, true, true, false);

                if (count($rows) < 2) {
                    continue;
                }

                $header = array_map(fn($v) => trim((string) $v), $rows[0]);

                foreach (array_slice($rows, 1) as $index => $row) {
                    $excelRowNumber = $index + 2;

                    if ($this->rowIsEmpty($row)) {
                        $skippedCount++;
                        continue;
                    }

                    try {
                        DB::beginTransaction();

                        $data = $this->mapImportedRow($header, $row);

                        if (
                            empty($data['ref_dossier']) &&
                            empty($data['destination']) &&
                            empty($data['service_name'])
                        ) {
                            DB::rollBack();
                            $skippedCount++;
                            continue;
                        }

                        $service = null;
                        if (!empty($data['service_name'])) {
                            $service = Service::firstOrCreate(
                                ['designation' => $data['service_name']],
                                [
                                    'type_service' => $defaultTypeService->id,
                                    'description' => 'Créé automatiquement via import Excel',
                                ]
                            );
                        }

                        $supplier = $this->findOrCreateSupplierSmart(
                            $data['supplier_name'] ?? null,
                            $defaultTypeSupplier
                        );

                        $driver = $this->findOrCreateDriverSmart($data['driver_name'] ?? null);
                        $guide = $this->findOrCreateGuideSmart($data['guide_name'] ?? null);

                        $existingPlanning = Planning::where('ref_dossier', $data['ref_dossier'])
                            ->where('date_du', $data['date_du'])
                            ->where('destination', $data['destination'])
                            ->first();

                        if (!$existingPlanning) {
                            $planning = Planning::create([
                                'date_du' => $data['date_du'],
                                'date_au' => $data['date_au'],
                                'ref_dossier' => $data['ref_dossier'],
                                'bus' => $data['bus'],
                                'nbr_personnes' => $data['nbr_personnes'],
                                'flight' => $data['flight'],
                                'heure' => $data['heure'],
                                'point_depart' => $data['point_depart'],
                                'destination' => $data['destination'],
                                'site' => $data['site'],
                                'service_id' => $service?->id,
                                'supplier_id' => $supplier?->id,
                                'driver_id' => $driver?->id,
                                'guide_id' => $guide?->id,
                                'budget' => $data['budget'],
                                'supplier_price' => $data['supplier_price'],
                                'notes' => 'Importé depuis Excel',
                            ]);

                            $createdCount++;
                        } else {
                            $existingPlanning->update([
                                'date_au' => $existingPlanning->date_au ?: $data['date_au'],
                                'bus' => $existingPlanning->bus ?: $data['bus'],
                                'nbr_personnes' => $existingPlanning->nbr_personnes ?: $data['nbr_personnes'],
                                'flight' => $existingPlanning->flight ?: $data['flight'],
                                'heure' => $existingPlanning->heure ?: $data['heure'],
                                'point_depart' => $existingPlanning->point_depart ?: $data['point_depart'],
                                'site' => $existingPlanning->site ?: $data['site'],
                                'service_id' => $existingPlanning->service_id ?: $service?->id,
                                'supplier_id' => $existingPlanning->supplier_id ?: $supplier?->id,
                                'driver_id' => $existingPlanning->driver_id ?: $driver?->id,
                                'guide_id' => $existingPlanning->guide_id ?: $guide?->id,
                                'budget' => $existingPlanning->budget ?: $data['budget'],
                                'supplier_price' => $existingPlanning->supplier_price ?: $data['supplier_price'],
                            ]);

                            $planning = $existingPlanning;
                            $updatedCount++;
                        }

                        $clientNames = $this->extractClientNames($data['clients_name']);

                        foreach ($clientNames as $clientName) {
                            $client = $this->findOrCreateClientSmart($clientName);

                            if (!$client) {
                                continue;
                            }

                            PlanningClient::firstOrCreate([
                                'planning_id' => $planning->id,
                                'client_id' => $client->id,
                            ]);
                        }

                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();

                        $errorCount++;
                        $errorsList[] = "Ligne {$excelRowNumber}: " . $e->getMessage();

                        continue;
                    }
                }
            }

            $message = "Import terminé. "
                . "{$createdCount} planning(s) créé(s), "
                . "{$updatedCount} mis à jour, "
                . "{$skippedCount} ligne(s) ignorée(s), "
                . "{$errorCount} erreur(s).";

            return redirect()->route('plannings.index')->with([
                'success' => $message,
                'import_errors' => $errorsList,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erreur import Excel : ' . $e->getMessage());
        }
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

    private function mapImportedRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $columnName) {
            $mapped[$columnName] = $row[$index] ?? null;
        }

        return [
            'date_du' => $this->normalizeExcelDate($mapped['DU'] ?? null),
            'date_au' => $this->normalizeExcelDate($mapped['AU'] ?? null),
            'ref_dossier' => $this->cleanString($mapped['REF DOSSIER'] ?? null),
            'bus' => $this->cleanString($mapped['bus'] ?? null),
            'nbr_personnes' => $this->toInteger($mapped['N/P'] ?? null),
            'service_name' => $this->cleanString($mapped['SERVICE'] ?? null),
            'flight' => $this->cleanString($mapped['Flight'] ?? null),
            'heure' => $this->normalizeTime($mapped['Heure'] ?? null),
            'point_depart' => $this->cleanString($mapped['point départ'] ?? null),
            'destination' => $this->cleanString($mapped['Destination'] ?? null),
            'site' => $this->cleanString($mapped['SITE'] ?? null),
            'supplier_name' => $this->cleanString($mapped['Supliers'] ?? null),
            'driver_name' => $this->cleanString($mapped['MD Driver'] ?? null),
            'guide_name' => $this->cleanString($mapped['Guide'] ?? null),
            'clients_name' => $mapped['Clients Name'] ?? null,
            'budget' => $this->toDecimal($mapped['budget'] ?? null),
            'supplier_price' => $this->toDecimal($mapped["supplier's price"] ?? null),
        ];
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

        return (float) $value;
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

    private function normalizePersonName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim((string) $value), 'UTF-8');

        if ($value === '') {
            return null;
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }

        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/(.)\1+/', '$1', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeAndSortName(?string $value): ?string
    {
        $value = $this->normalizePersonName($value);

        if (!$value) {
            return null;
        }

        $parts = array_filter(explode(' ', $value));
        sort($parts, SORT_STRING);

        return implode(' ', $parts);
    }

    private function nameSimilarityScore(?string $a, ?string $b): float
    {
        $a = $this->normalizeAndSortName($a);
        $b = $this->normalizeAndSortName($b);

        if (!$a || !$b) {
            return 0;
        }

        similar_text($a, $b, $similarPercent);

        $maxLen = max(strlen($a), strlen($b));
        $levScore = $maxLen > 0
            ? (1 - (levenshtein($a, $b) / $maxLen)) * 100
            : 100;

        return round(($similarPercent + $levScore) / 2, 2);
    }

    private function findClosestByName(string $modelClass, string $column, ?string $value, float $threshold = 88): ?object
    {
        $value = $this->cleanString($value);

        if (!$value) {
            return null;
        }

        $rows = $modelClass::query()
            ->select(['id', $column])
            ->get();

        $best = null;
        $bestScore = 0;

        foreach ($rows as $row) {
            $candidate = $row->{$column} ?? null;

            if (!$candidate) {
                continue;
            }

            $score = $this->nameSimilarityScore($value, $candidate);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $bestScore >= $threshold ? $best : null;
    }

    private function findOrCreateSupplierSmart(?string $name, $defaultTypeSupplier): ?Supplier
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        $existing = $this->findClosestByName(Supplier::class, 'name', $name, 90);

        if ($existing) {
            return Supplier::find($existing->id);
        }

        $user = null;

        try {
            $user = $this->createSystemUserIfNotExists($name, 'supplier');
        } catch (\Throwable $e) {
            // ma nw9fouch import ila user creation t3tlat
            $user = null;
        }

        $data = [
            'name' => $name,
            'type' => $defaultTypeSupplier->id,
            'phone' => null,
            'email' => $user?->email,
            'address' => null,
            'notes' => 'Créé automatiquement via import Excel',
        ];

        if (Schema::hasColumn('suppliers', 'user_id')) {
            $data['user_id'] = $user?->id;
        }

        return Supplier::create($data);
    }

    private function findOrCreateDriverSmart(?string $name): ?Driver
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        $existing = $this->findClosestByName(Driver::class, 'name', $name, 88);

        if ($existing) {
            return Driver::find($existing->id);
        }

        $user = $this->createSystemUserIfNotExists($name, 'driver');

        return Driver::create([
            'name' => $name,
            'phone' => null,
            'email' => $user?->email,
            'status' => 'Disponible',
            'notes' => 'Créé automatiquement via import Excel',
            'user_id' => $user?->id,
        ]);
    }

    private function findOrCreateGuideSmart(?string $name): ?Guide
    {
        $name = $this->cleanString($name);

        if (!$name) {
            return null;
        }

        $existing = $this->findClosestByName(Guide::class, 'name', $name, 88);

        if ($existing) {
            return Guide::find($existing->id);
        }

        $user = null;

        try {
            $user = $this->createSystemUserIfNotExists($name, 'guide');
        } catch (\Throwable $e) {
            $user = null;
        }

        $data = [
            'name' => $name,
            'phone' => null,
            'email' => $user?->email,
            'status' => 'Disponible',
            'notes' => 'Créé automatiquement via import Excel',
        ];

        if (Schema::hasColumn('guides', 'user_id')) {
            $data['user_id'] = $user?->id;
        }

        return Guide::create($data);
    }

    private function findOrCreateClientSmart(?string $fullName): ?Client
    {
        $fullName = $this->cleanString($fullName);

        if (!$fullName) {
            return null;
        }

        $existing = $this->findClosestByName(Client::class, 'full_name', $fullName, 90);

        if ($existing) {
            return Client::find($existing->id);
        }

        return Client::create([
            'full_name' => $fullName,
            'phone' => null,
            'email' => null,
            'notes' => 'Créé automatiquement via import Excel',
        ]);
    }

    private function createSystemUserIfNotExists(string $name, string $role): ?User
    {
        $email = $this->makeEmailFromName($name);

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            try {
                if (
                    method_exists($existingUser, 'assignRole')
                    && !$existingUser->hasRole($role)
                ) {
                    $existingUser->assignRole($role);
                }
            } catch (\Throwable $e) {
                // ma nw9fouch import
            }

            return $existingUser;
        }

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($email),
        ];

        if (Schema::hasColumn('users', 'active')) {
            $userData['active'] = 1;
        }

        $user = User::create($userData);

        try {
            if (method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            }
        } catch (\Throwable $e) {
            // ma nw9fouch import
        }

        return $user;
    }


    private function makeEmailFromName(string $name): string
    {
        $name = trim($name);

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($ascii !== false) {
            $name = $ascii;
        }

        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', '', $name);

        if (empty($name)) {
            $name = 'user' . now()->timestamp;
        }

        $email = $name . '@gmail.com';
        $base = $name;
        $i = 1;

        while (User::where('email', $email)->exists()) {
            $email = $base . $i . '@gmail.com';
            $i++;
        }

        return $email;
    }
}
