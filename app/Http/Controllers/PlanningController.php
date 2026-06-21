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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;

class PlanningController extends Controller
{
    private ?array $supplierVehiculeImportNames = null;

    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_du')
            ? Carbon::parse($request->date_du)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->filled('date_au')
            ? Carbon::parse($request->date_au)->endOfDay()
            : now()->endOfMonth();

        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();

        $periodQuery = Planning::query()
            ->whereBetween('date_du', [$dateFromString, $dateToString]);

        $query = Planning::with([
            'supplierClient',
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client.supplierClient',
        ])->whereBetween('date_du', [$dateFromString, $dateToString]);

        $this->applyExactFilter($query, $request, 'filter_date_du', 'date_du');
        $this->applyExactFilter($query, $request, 'filter_date_au', 'date_au');
        $this->applyExactFilter($query, $request, 'ref_dossier');
        $this->applyExactFilter($query, $request, 'nbr_personnes');
        $this->applyExactFilter($query, $request, 'flight');
        $this->applyExactFilter($query, $request, 'heure');
        $this->applyExactFilter($query, $request, 'point_depart');
        $this->applyExactFilter($query, $request, 'site');
        $this->applyExactFilter($query, $request, 'supplier_vehicule_id');
        $this->applyExactFilter($query, $request, 'destination_id');
        $this->applyExactFilter($query, $request, 'vehicule_id');
        $this->applyExactFilter($query, $request, 'driver_id');
        $this->applyExactFilter($query, $request, 'guide_id');
        $this->applyExactFilter($query, $request, 'service_id');
        $this->applyExactFilter($query, $request, 'budget');
        $this->applyExactFilter($query, $request, 'supplier_price');

        if ($this->hasFilterValue($request, 'supplier_client_id')) {
            $values = $this->filterValues($request, 'supplier_client_id');

            $query->where(function ($supplierQuery) use ($values) {
                $supplierQuery
                    ->whereIn('supplier_client_id', $values)
                    ->orWhereHas('planningClients.client', function ($sub) use ($values) {
                        $sub->whereIn('supplier_client_id', $values);
                    });
            });
        }

        if ($this->hasFilterValue($request, 'client_id')) {
            $values = $this->filterValues($request, 'client_id');

            $query->whereHas('planningClients', function ($sub) use ($values) {
                $sub->whereIn('client_id', $values);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('ref_dossier', 'like', "%{$search}%")
                    ->orWhere('flight', 'like', "%{$search}%")
                    ->orWhere('point_depart', 'like', "%{$search}%")
                    ->orWhere('site', 'like', "%{$search}%")
                    ->orWhereHas('supplierClient', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplierVehicule', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('planningClients.client.supplierClient', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('guide', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('service', fn($sub) => $sub->where('designation', 'like', "%{$search}%"))
                    ->orWhereHas('destination', fn($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))
                    ->orWhereHas('vehicule', fn($sub) => $sub->where('matricule', 'like', "%{$search}%"))
                    ->orWhereHas('planningClients.client', fn($sub) => $sub->where('full_name', 'like', "%{$search}%"));
            });
        }

        $sortColumn = $this->sortColumn($request->input('sort_column'));
        $sortDirection = $request->input('sort_direction') === 'asc' ? 'asc' : 'desc';

        if ($sortColumn) {
            $query->orderBy($sortColumn, $sortDirection);
        } elseif ($request->boolean('use_manual_order')) {
            $query
                ->orderByRaw('manual_order IS NULL')
                ->orderBy('manual_order');
        }

        $plannings = $query
            ->orderByDesc('date_du')
            ->orderBy('heure')
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
            'columnFilters' => [
                'start_dates' => $this->distinctPlanningDates($periodQuery, 'date_du'),
                'end_dates' => $this->distinctPlanningDates($periodQuery, 'date_au'),
                'references' => $this->distinctPlanningValues($periodQuery, 'ref_dossier'),
                'paxes' => $this->distinctPlanningValues($periodQuery, 'nbr_personnes'),
                'flights' => $this->distinctPlanningValues($periodQuery, 'flight'),
                'times' => $this->distinctPlanningTimes($periodQuery),
                'start_points' => $this->distinctPlanningValues($periodQuery, 'point_depart'),
                'locations' => $this->distinctPlanningValues($periodQuery, 'site'),
                'budgets' => $this->distinctPlanningValues($periodQuery, 'budget'),
                'supplier_prices' => $this->distinctPlanningValues($periodQuery, 'supplier_price'),
            ],
            'filters' => [
                'date_du' => $dateFromString,
                'date_au' => $dateToString,
                'filter_date_du' => $request->input('filter_date_du', []),
                'filter_date_au' => $request->input('filter_date_au', []),
                'ref_dossier' => $request->ref_dossier ?? '',
                'nbr_personnes' => $request->nbr_personnes ?? '',
                'flight' => $request->flight ?? '',
                'heure' => $request->heure ?? '',
                'point_depart' => $request->point_depart ?? '',
                'site' => $request->site ?? '',
                'supplier_client_id' => $request->supplier_client_id ?? '',
                'supplier_vehicule_id' => $request->supplier_vehicule_id ?? '',
                'driver_id' => $request->driver_id ?? '',
                'guide_id' => $request->guide_id ?? '',
                'service_id' => $request->service_id ?? '',
                'destination_id' => $request->destination_id ?? '',
                'vehicule_id' => $request->vehicule_id ?? '',
                'client_id' => $request->input('client_id', []),
                'budget' => $request->input('budget', []),
                'supplier_price' => $request->input('supplier_price', []),
                'sort_column' => $request->sort_column ?? '',
                'sort_direction' => $request->sort_direction ?? '',
                'use_manual_order' => $request->boolean('use_manual_order') ? '1' : '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:plannings,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? count($data['ordered_ids']));
        $startPosition = (($page - 1) * $perPage) + 1;

        DB::transaction(function () use ($data, $startPosition) {
            foreach ($data['ordered_ids'] as $index => $id) {
                Planning::whereKey($id)->update([
                    'manual_order' => $startPosition + $index,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Planning order saved successfully.');
    }

    private function hasFilterValue(Request $request, string $param): bool
    {
        return count($this->filterValues($request, $param)) > 0;
    }

    private function filterValues(Request $request, string $param): array
    {
        $value = $request->input($param, []);
        $values = is_array($value) ? $value : [$value];

        return collect($values)
            ->map(fn ($item) => is_string($item) ? trim($item) : $item)
            ->reject(fn ($item) => $item === null || $item === '')
            ->map(fn ($item) => (string) $item)
            ->unique()
            ->values()
            ->all();
    }

    private function applyExactFilter($query, Request $request, string $param, ?string $column = null): void
    {
        if (!$this->hasFilterValue($request, $param)) {
            return;
        }

        $query->whereIn($column ?: $param, $this->filterValues($request, $param));
    }

    private function sortColumn(?string $column): ?string
    {
        return [
            'filter_date_du' => 'date_du',
            'filter_date_au' => 'date_au',
            'ref_dossier' => 'ref_dossier',
            'vehicule_id' => 'vehicule_id',
            'nbr_personnes' => 'nbr_personnes',
            'service_id' => 'service_id',
            'flight' => 'flight',
            'heure' => 'heure',
            'point_depart' => 'point_depart',
            'destination_id' => 'destination_id',
            'site' => 'site',
            'supplier_client_id' => 'supplier_client_id',
            'supplier_vehicule_id' => 'supplier_vehicule_id',
            'driver_id' => 'driver_id',
            'guide_id' => 'guide_id',
            'budget' => 'budget',
            'supplier_price' => 'supplier_price',
        ][$column] ?? null;
    }

    private function distinctPlanningValues($periodQuery, string $column)
    {
        return (clone $periodQuery)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value) => (string) $value)
            ->values();
    }

    private function distinctPlanningTimes($periodQuery)
    {
        return (clone $periodQuery)
            ->whereNotNull('heure')
            ->distinct()
            ->orderBy('heure')
            ->pluck('heure')
            ->map(fn ($value) => Carbon::parse($value)->format('H:i'))
            ->values();
    }

    private function distinctPlanningDates($periodQuery, string $column)
    {
        return (clone $periodQuery)
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value) => Carbon::parse($value)->toDateString())
            ->values();
    }

    public function store(Request $request)
    {
        $data = $this->validatePlanning($request);

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

            return redirect()->back()->with('success', 'Planning added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Planning creation error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);
        $data = $this->validatePlanning($request);

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

            return redirect()->back()->with('success', 'Planning updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Planning update error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Planning::findOrFail($id)->delete();

            return redirect()->back()->with('success', 'Planning deleted successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Planning deletion error: ' . $e->getMessage());
        }
    }

    private function validatePlanning(Request $request): array
    {
        return $request->validate([
            'date_du' => 'required|date',
            'ref_dossier' => 'required|string|max:255',
            'date_au' => 'nullable|date',
            'nbr_personnes' => 'nullable|integer',
            'flight' => 'nullable|string|max:255',
            'heure' => 'nullable',
            'point_depart' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:255',

            'service_id' => 'nullable|exists:services,id',
            'supplier_client_id' => 'nullable|exists:supplier_clients,id',
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
    }

    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);
        @ini_set('memory_limit', '2048M');

        Log::info('Planning Excel import request received', [
            'has_file' => $request->hasFile('file'),
            'file_keys' => array_keys($request->allFiles()),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);

        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls'],
                'import_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Planning Excel import validation failed', [
                'error' => $e->getMessage(),
                'has_file' => $request->hasFile('file'),
                'file_keys' => array_keys($request->allFiles()),
                'content_length' => $request->server('CONTENT_LENGTH'),
            ]);

            return redirect()->back()->with([
                'error' => 'Invalid file: ' . $e->getMessage(),
                'import_errors' => ['No Excel file was received by the server. Please select the file again and retry.'],
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $debug = [];

        try {
            // L3am li ghadi ntb9oh 3la Excel, matalan fichier dyal 2026 => import_year = 2026
            $importYear = (int) ($request->input('import_year') ?: now()->year);
            $uploadedFile = $request->file('file');

            Log::info('Planning Excel import started', [
                'file_name' => $uploadedFile?->getClientOriginalName(),
                'file_size' => $uploadedFile?->getSize(),
                'mime_type' => $uploadedFile?->getClientMimeType(),
                'import_year' => $importYear,
            ]);

            $spreadsheet = IOFactory::load($uploadedFile->getPathname());
            $sheetNames = $spreadsheet->getSheetNames();

            Log::info('Planning Excel import sheets detected', [
                'sheet_names' => $sheetNames,
            ]);

            $defaultTypeService = TypeService::firstOrCreate([
                'designation' => 'Imported',
            ]);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $rows = $sheet->toArray(null, true, true, false);
                $sheetName = $sheet->getTitle();
                $sheetCreated = 0;
                $sheetUpdated = 0;
                $sheetSkipped = 0;
                $sheetErrors = 0;

                if (count($rows) < 2) {
                    Log::warning('Planning Excel import skipped sheet: not enough rows', [
                        'sheet' => $sheetName,
                        'rows' => count($rows),
                    ]);
                    continue;
                }

                $headerIndex = $this->findHeaderRowIndex($rows);

                if ($headerIndex === null) {
                    $message = "Sheet {$sheetName}: header row not found.";
                    $skipped += count($rows);
                    $errors[] = $message;

                    Log::warning('Planning Excel import header row not found', [
                        'sheet' => $sheetName,
                        'rows' => count($rows),
                        'sample_rows' => array_slice($rows, 0, 5),
                    ]);

                    return redirect()->back()->with([
                        'error' => $message,
                        'import_errors' => array_slice($errors, 0, 300),
                        'import_debug' => $debug,
                    ]);
                }

                $header = array_map(fn($value) => $this->normalizeHeader($value), $rows[$headerIndex]);
                $dataRows = array_slice($rows, $headerIndex + 1);
                $totalDataRows = collect($dataRows)->reject(fn($row) => $this->rowIsEmpty($row))->count();

                Log::info('Planning Excel import sheet headers detected', [
                    'sheet' => $sheetName,
                    'header_row' => $headerIndex + 1,
                    'normalized_headers' => $header,
                    'total_data_rows' => $totalDataRows,
                ]);

                foreach ($dataRows as $index => $row) {
                    $line = $headerIndex + $index + 2;

                    if ($this->rowIsEmpty($row)) {
                        $skipped++;
                        $sheetSkipped++;
                        continue;
                    }

                    DB::beginTransaction();

                    try {
                        $data = $this->mapImportedRow($header, $row, $importYear, $sheetName);
                        $data = $this->fixShiftedPassengerGuideColumns($data);
                        $data['date_au'] = $this->fixEndDateYear($data['date_du'], $data['date_au']);

                        // Ila REF DOSSIER khawi, kansaybo ref automatique bach row ma ytzgalch.
                        if (!$data['ref_dossier']) {
                            $data['ref_dossier'] = $this->generateFallbackRef($sheetName, $line, $data);
                        }

                        // Ila date khawya, had row ma n9drouch ndakhloha f planning hit date_du required.
                        if (!$data['date_du']) {
                            DB::rollBack();
                            $skipped++;
                            $sheetSkipped++;
                            $message = "Sheet {$sheetName} - Line {$line}: date is empty or invalid.";
                            $errors[] = $message;

                            Log::warning('Planning Excel import skipped row', [
                                'sheet' => $sheetName,
                                'line' => $line,
                                'reason' => 'date is empty or invalid',
                                'row' => $row,
                            ]);

                            continue;
                        }

                        $service = $this->firstOrCreateService($data['service_name'], $defaultTypeService);

                        // Column "Supliers" = supplier for clients
                        $supplierClient = $this->firstOrCreateSupplierClient($data['supplier_client_name']);

                        // Column "MD Driver" = driver name
                        $driver = $this->firstOrCreateDriver($data['driver_name']);

                        // Column "Supleirs Vehicule" = supplier vehicule.
                        // Ila khawi, kandiro MD DRIVE automatiquement bach mayb9ach null.
                        $supplierVehicule = $this->firstOrCreateSupplierVehicule(
                            $this->isPlaceholder($data['supplier_vehicule_name']) ? 'MD DRIVE' : $data['supplier_vehicule_name']
                        );

                        // Column "Vehicle" = vehicle matricule/type stored in vehicules
                        $vehicule = $this->firstOrCreateVehicule($data['vehicule_name']);

                        $destination = $this->firstOrCreateDestination($data['destination_name']);
                        $guide = $this->firstOrCreateGuide($data['guide_name']);

                        $importNote = 'Imported from Excel - Sheet: ' . $sheetName . ' - Line: ' . $line;
                        $planning = $this->findExistingImportedPlanning($data, $importNote);

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
                            'supplier_client_id' => $supplierClient?->id,
                            'supplier_vehicule_id' => $supplierVehicule?->id,
                            'driver_id' => $driver?->id,
                            'guide_id' => $guide?->id,
                            'destination_id' => $destination?->id,
                            'vehicule_id' => $vehicule?->id,

                            'budget' => $data['budget'],
                            'supplier_price' => $data['supplier_price'],
                            'notes' => $importNote,
                        ];

                        if ($planning) {
                            $planning->update($planningData);
                            PlanningClient::where('planning_id', $planning->id)->delete();
                            $updated++;
                            $sheetUpdated++;
                        } else {
                            $planning = Planning::create($planningData);
                            $created++;
                            $sheetCreated++;
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
                        $skipped++;
                        $sheetSkipped++;
                        $sheetErrors++;
                        $message = "Sheet {$sheetName} - Line {$line}: " . $e->getMessage();
                        $errors[] = $message;

                        Log::warning('Planning Excel import row error', [
                            'sheet' => $sheet->getTitle(),
                            'line' => $line,
                            'error' => $e->getMessage(),
                            'row' => $row,
                        ]);
                    }
                }

                $debug[] = [
                    'sheet' => $sheetName,
                    'header_row' => $headerIndex + 1,
                    'total_data_rows' => $totalDataRows,
                    'created' => $sheetCreated,
                    'updated' => $sheetUpdated,
                    'skipped' => $sheetSkipped,
                    'errors' => $sheetErrors,
                ];

                Log::info('Planning Excel import sheet completed', end($debug));
            }

            $summary = [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => count($errors),
                'sheets' => $debug,
            ];

            Log::info('Planning Excel import completed', $summary);

            if ($created === 0 && $updated === 0) {
                $message = "Import failed: 0 created, 0 updated, {$skipped} skipped, " . count($errors) . " error(s).";

                Log::warning('Planning Excel import finished without inserts or updates', $summary);

                return redirect()->back()->with([
                    'error' => $message,
                    'import_errors' => array_slice($errors, 0, 300),
                    'import_debug' => $debug,
                ]);
            }

            return redirect()->route('plannings.index')->with([
                'success' => "Import completed: {$created} created, {$updated} updated, {$skipped} skipped, " . count($errors) . " error(s).",
                'import_errors' => array_slice($errors, 0, 300),
                'import_debug' => $debug,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Planning Excel import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Excel import error: ' . $e->getMessage());
        }
    }

    private function findExistingImportedPlanning(array $data, ?string $importNote = null): ?Planning
    {
        if ($importNote) {
            return Planning::where('notes', $importNote)->first();
        }

        $query = Planning::where('ref_dossier', $data['ref_dossier'])
            ->where('date_du', $data['date_du']);

        if ($data['heure']) {
            $query->where('heure', $data['heure']);
        }

        if ($data['flight']) {
            $query->where('flight', $data['flight']);
        }

        return $query->first();
    }

    private function findHeaderRowIndex(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $headers = array_map(fn($value) => $this->normalizeHeader($value), $row);

            $hasStartDate = in_array('du', $headers, true)
                || in_array('from', $headers, true)
                || in_array('start date', $headers, true)
                || in_array('star date', $headers, true); // kayna f fichier b smiya Star Date

            $hasRef = in_array('ref dossier', $headers, true)
                || in_array('invoice reference', $headers, true)
                || in_array('ref', $headers, true)
                || in_array('dossier', $headers, true);

            if ($hasStartDate && $hasRef) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeHeader($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = str_replace(["\n", "\r"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = str_replace(["’", "`", "´", "‘"], "'", $value);
        $value = mb_strtolower($value);

        // Kan7iydo accents bach "départ" twali "depart".
        $value = strtr($value, [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
        ]);

        return trim($value);
    }

    private function getMappedValue(array $mapped, array $keys)
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeHeader($key);

            if (array_key_exists($normalizedKey, $mapped)) {
                return $mapped[$normalizedKey];
            }
        }

        return null;
    }

    private function mapImportedRow(array $header, array $row, int $importYear, ?string $sheetName = null): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $mapped[$key] = $row[$index] ?? null;
        }

        return [
            'date_du' => $this->normalizeExcelDate($this->getMappedValue($mapped, [
                'du',
                'from',
                'start date',
                'star date'
            ]), $importYear, $sheetName),

            'date_au' => $this->normalizeExcelDate($this->getMappedValue($mapped, [
                'au',
                'to',
                'end date'
            ]), $importYear, $sheetName),

            'ref_dossier' => $this->cleanString($this->getMappedValue($mapped, [
                'ref dossier',
                'invoice reference',
                'ref',
                'dossier'
            ])),

            // Excel "Vehicle" goes to vehicules table.
            'vehicule_name' => $this->cleanString($this->getMappedValue($mapped, [
                'vehicle',
                'vehicle type',
                'bus',
                'vehicule',
                'véhicule'
            ])),

            // Excel "N/P" goes to plannings.nbr_personnes.
            'nbr_personnes' => $this->toInteger($this->getMappedValue($mapped, [
                'n/p',
                'np',
                'nbr personnes',
                'nombre personnes',
                'pax'
            ])),

            'service_name' => $this->cleanString($this->getMappedValue($mapped, [
                'type',
                'service',
                'service type'
            ])),

            'flight' => $this->cleanString($this->getMappedValue($mapped, [
                'flight',
                'flight info',
                'vol'
            ])),

            'heure' => $this->normalizeTime($this->getMappedValue($mapped, [
                'time',
                'heure'
            ])),

            'point_depart' => $this->cleanString($this->getMappedValue($mapped, [
                'start point',
                'point depart',
                'point départ',
                'point dep',
                'depart'
            ])),

            'destination_name' => $this->cleanString($this->getMappedValue($mapped, [
                'end point',
                'destination',
                'arrivee',
                'arrivée'
            ])),

            'site' => $this->cleanString($this->getMappedValue($mapped, [
                'site',
                'city',
                'location'
            ])),

            // Excel "Supliers" = client supplier.
            'supplier_client_name' => $this->cleanString($this->getMappedValue($mapped, [
                'supliers',
                'suppliers',
                'supplier',
                'suppliers client',
                'supplier client',
                'client supplier'
            ])),

            // Excel "Supleirs Vehicule" = vehicle supplier. Ila khawi, importExcel ghadi idir MD DRIVE.
            'supplier_vehicule_name' => $this->cleanString($this->getMappedValue($mapped, [
                'supleirs vehicule',
                'supliers vehicule',
                'suppliers vehicule',
                'supleirs vehicle',
                'supliers vehicle',
                'suppliers vehicle',
                'supplier vehicule',
                'supplier vehicle',
                'vehicle supplier'
            ])),

            // Excel "MD Driver" = drivers table.
            'driver_name' => $this->cleanString($this->getMappedValue($mapped, [
                'md driver',
                'md drive',
                'driver'
            ])),

            'guide_name' => $this->cleanString($this->getMappedValue($mapped, [
                'guide'
            ])),

            'clients_name' => $this->getMappedValue($mapped, [
                'passenger names',
                'passengers name',
                'passenger name',
                'clients name',
                'client name',
                'clients',
                'client'
            ]),

            'budget' => $this->toDecimal($this->getMappedValue($mapped, [
                'budget',
                'md budget',
                'md price'
            ])),

            'supplier_price' => $this->toDecimal($this->getMappedValue($mapped, [
                "supplier's price",
                'supplier price',
                'supplier budget',
                'price'
            ])),
        ];
    }

    private function firstOrCreateSupplierClient(?string $name): ?SupplierClient
    {
        $name = $this->cleanString($name);

        if (!$name || $this->isPlaceholder($name)) {
            return null;
        }

        return SupplierClient::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => 'Created automatically from Excel import',
                'is_active' => 1,
            ]
        );
    }

    private function firstOrCreateSupplierVehicule(?string $name): ?SupplierVehicule
    {
        $name = $this->cleanString($name);

        if (!$name || $this->isPlaceholder($name)) {
            return null;
        }

        return SupplierVehicule::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => 'Created automatically from Excel import',
                'is_active' => 1,
            ]
        );
    }

    private function firstOrCreateDestination(?string $name): ?Destination
    {
        $name = $this->cleanString($name);

        if (!$name || $this->isPlaceholder($name)) {
            return null;
        }

        return Destination::firstOrCreate(
            ['name' => $name],
            [
                'city' => null,
                'country' => 'Morocco',
                'type' => null,
                'status' => 'Active',
                'notes' => 'Created automatically from Excel import',
            ]
        );
    }

    private function firstOrCreateVehicule(?string $matricule): ?Vehicule
    {
        $matricule = $this->cleanString($matricule);

        if (!$matricule || $this->isPlaceholder($matricule)) {
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
                'status' => 'Available',
                'notes' => 'Created automatically from Excel import',
            ]
        );
    }

    private function firstOrCreateService(?string $designation, TypeService $defaultTypeService): ?Service
    {
        $designation = $this->cleanString($designation);

        if (!$designation || $this->isPlaceholder($designation)) {
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

        if (!$name || $this->isPlaceholder($name)) {
            return null;
        }

        return Guide::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'status' => 'Available',
                'notes' => 'Created automatically from Excel import',
            ]
        );
    }

    private function fixShiftedPassengerGuideColumns(array $data): array
    {
        $guideName = $this->cleanString($data['guide_name'] ?? null);
        $clientsName = $this->cleanString($data['clients_name'] ?? null);
        $clientsNameIsAmount = $this->looksLikeDecimal($clientsName);

        // Some monthly sheets have shifted values after the Guide column:
        // Guide => passenger/supplier vehicle, Passenger Names => budget,
        // budget => supplier price.
        if ($clientsNameIsAmount) {
            if (($data['supplier_price'] ?? null) === null && ($data['budget'] ?? null) !== null) {
                $data['supplier_price'] = $data['budget'];
            }

            $data['budget'] = $this->toDecimal($clientsName);
            $data['clients_name'] = null;

            if ($this->looksLikePassengerList($guideName)) {
                $data['clients_name'] = $guideName;
                $data['guide_name'] = null;
            } elseif ($this->shouldTreatGuideAsSupplierVehicule($guideName, $data['supplier_vehicule_name'] ?? null)) {
                $data['supplier_vehicule_name'] = $guideName;
                $data['guide_name'] = null;
            }

            return $data;
        }

        if (!$guideName) {
            return $data;
        }

        $clientsMissingOrWrong = !$clientsName
            || is_numeric($clientsName)
            || preg_match('/^\d+([.,]\d+)?$/', $clientsName);

        if ($this->looksLikePassengerList($guideName) && $clientsMissingOrWrong) {
            $data['clients_name'] = $guideName;
            $data['guide_name'] = null;
        } elseif ($this->shouldTreatGuideAsSupplierVehicule($guideName, $data['supplier_vehicule_name'] ?? null)) {
            $data['supplier_vehicule_name'] = $guideName;
            $data['guide_name'] = null;
        }

        return $data;
    }

    private function looksLikePassengerList(?string $value): bool
    {
        $value = $this->cleanString($value);

        if (!$value) {
            return false;
        }

        return mb_strlen($value) > 190
            || str_contains($value, "\n")
            || preg_match('/\b(Mr|Mrs|Ms|Miss|Dr|Family|Party)\b/i', $value);
    }

    private function shouldTreatGuideAsSupplierVehicule(?string $guideName, ?string $supplierVehiculeName): bool
    {
        $guideName = $this->cleanString($guideName);

        if (!$guideName || !$this->isPlaceholder($supplierVehiculeName)) {
            return false;
        }

        $normalized = $this->normalizeHeader($guideName);

        if (in_array($normalized, $this->supplierVehiculeImportNames(), true)) {
            return true;
        }

        return preg_match('/\b(tour|tours|transport|shuttle|ride|hiking|travel|amdk|srtt|md drive)\b/i', $guideName) === 1;
    }

    private function supplierVehiculeImportNames(): array
    {
        if ($this->supplierVehiculeImportNames !== null) {
            return $this->supplierVehiculeImportNames;
        }

        $this->supplierVehiculeImportNames = SupplierVehicule::query()
            ->pluck('name')
            ->map(fn ($name) => $this->normalizeHeader($name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->supplierVehiculeImportNames;
    }

    private function firstOrCreateDriver(?string $name): ?Driver
    {
        $name = $this->cleanString($name);

        if (!$name || $this->isPlaceholder($name)) {
            return null;
        }

        $normalized = $this->normalizeDriverName($name);
        $existingDriver = Driver::query()
            ->get()
            ->first(fn (Driver $driver) => $this->normalizeDriverName($driver->name) === $normalized);

        if ($existingDriver) {
            return $existingDriver;
        }

        return Driver::firstOrCreate(
            ['name' => $name],
            [
                'phone' => null,
                'email' => null,
                'status' => 'Available',
                'notes' => 'Created automatically from Excel import',
            ]
        );
    }

    private function normalizeDriverName(?string $name): string
    {
        $value = (string) Str::of((string) $name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();

        return collect(explode(' ', $value))
            ->filter()
            ->sort()
            ->values()
            ->implode(' ');
    }

    private function firstOrCreateClient(string $fullName, ?int $supplierClientId = null): Client
    {
        $fullName = $this->cleanString($fullName);

        $lookup = ['full_name' => $fullName];

        if ($supplierClientId) {
            $lookup['supplier_client_id'] = $supplierClientId;
        }

        $client = Client::firstOrCreate($lookup, [
            'phone' => null,
            'email' => null,
            'notes' => 'Created automatically from Excel import',
        ]);

        if ($supplierClientId && !$client->supplier_client_id) {
            $client->update(['supplier_client_id' => $supplierClientId]);
        }

        return $client;
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
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace('/\n{3,}/', "\n", $value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function isPlaceholder(?string $value): bool
    {
        if (!$value) {
            return true;
        }

        $v = mb_strtolower(trim($value));

        return in_array($v, [
            'xxxx',
            'xxx',
            'xx',
            'none',
            'null',
            'n/a',
            '-',
            '--',
            '/',
            '0'
        ], true);
    }

    private function toInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($this->isPlaceholder((string) $value)) {
            return null;
        }

        $value = preg_replace('/[^0-9]/', '', (string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function toDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($this->isPlaceholder((string) $value)) {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], (string) $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return $value === '' ? null : (float) $value;
    }

    private function looksLikeDecimal($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if ($this->isPlaceholder((string) $value)) {
            return false;
        }

        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        return preg_match('/^-?\d+(\.\d+)?$/', trim($value)) === 1;
    }

    private function normalizeExcelDate($value, int $importYear, ?string $sheetName = null): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);

                // Ila Excel fih serial date, kanforciw l3am dyal import.
                return Carbon::create($importYear, (int) $date->format('m'), (int) $date->format('d'))->format('Y-m-d');
            }

            $value = trim((string) $value);

            if ($this->isPlaceholder($value)) {
                return null;
            }

            $parsedDayMonth = $this->parseDayMonthDate($value, $importYear, $sheetName);

            if ($parsedDayMonth) {
                return $parsedDayMonth;
            }

            // Examples: 1-Jan, 31-Dec
            if (preg_match('/^\d{1,2}-[a-zA-Z]{3}$/', $value)) {
                return Carbon::parse($value . '-' . $importYear)->format('Y-m-d');
            }

            // Examples: 01/01, 31/12
            if (preg_match('/^\d{1,2}\/\d{1,2}$/', $value)) {
                [$day, $month] = explode('/', $value);

                return Carbon::create($importYear, (int) $month, (int) $day)->format('Y-m-d');
            }

            $date = Carbon::parse($value);

            // Ila Excel ja fih date b year ghalat, kanb9aw kanforciw l3am dyal import.
            return Carbon::create($importYear, (int) $date->format('m'), (int) $date->format('d'))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDayMonthDate(string $value, int $importYear, ?string $sheetName = null): ?string
    {
        $normalized = $this->normalizeHeader($value);
        $normalized = str_replace(['.', ','], '', $normalized);

        if (!preg_match('/^(\d{1,2})\s*[-\/ ]\s*([a-z]+)$/u', $normalized, $matches)) {
            return null;
        }

        $day = (int) $matches[1];
        $monthToken = $matches[2];
        $month = $this->monthNumberFromToken($monthToken, $sheetName);

        if (!$month) {
            return null;
        }

        try {
            return Carbon::create($importYear, $month, $day)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function monthNumberFromToken(string $token, ?string $sheetName = null): ?int
    {
        $token = $this->normalizeHeader($token);
        $sheet = $this->normalizeHeader($sheetName ?? '');

        $months = [
            1 => ['jan', 'janvier', 'january'],
            2 => ['feb', 'fev', 'fevrier', 'february'],
            3 => ['mar', 'mars', 'march'],
            4 => ['apr', 'avr', 'avril', 'april'],
            5 => ['may', 'mai'],
            6 => ['jun', 'juin', 'june'],
            7 => ['jul', 'juil', 'juillet', 'july'],
            8 => ['aug', 'aou', 'aout', 'august'],
            9 => ['sep', 'sept', 'septembre', 'september'],
            10 => ['oct', 'octobre', 'october'],
            11 => ['nov', 'novembre', 'november'],
            12 => ['dec', 'decembre', 'december'],
        ];

        foreach ($months as $month => $aliases) {
            if (in_array($token, $aliases, true)) {
                return $month;
            }
        }

        if ($token === 'ju') {
            if (str_contains($sheet, 'juillet') || str_contains($sheet, 'july')) {
                return 7;
            }

            if (str_contains($sheet, 'juin') || str_contains($sheet, 'june')) {
                return 6;
            }
        }

        return null;
    }

    private function fixEndDateYear(?string $dateDu, ?string $dateAu): ?string
    {
        if (!$dateAu || !$dateDu) {
            return $dateAu;
        }

        $start = Carbon::parse($dateDu);
        $end = Carbon::parse($dateAu);

        // Ila end date jat sghira mn start date, ya3ni dak trip daz l3am jdid.
        if ($end->lt($start)) {
            $end->addYear();
        }

        return $end->format('Y-m-d');
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

            if ($this->isPlaceholder($value)) {
                return null;
            }

            // Fix common typo: 08HOO / 08hoo => 08H00
            $value = preg_replace('/[oO]/', '0', $value);
            $value = str_replace(['H', 'h'], ':', $value);
            $value = preg_replace('/\s+/', '', $value);

            if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
                return Carbon::createFromFormat('H:i', $value)->format('H:i:s');
            }

            if (preg_match('/^\d{3,4}$/', $value)) {
                $value = str_pad($value, 4, '0', STR_PAD_LEFT);
                return substr($value, 0, 2) . ':' . substr($value, 2, 2) . ':00';
            }

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

        // Ila kaynin clients f lignes, comma, semicolon, aw dash separator.
        $parts = preg_split('/\n|,|;|\s{2,}|\s+-\s+/', $text);

        return collect($parts)
            ->map(fn($item) => $this->cleanString($item))
            ->filter(fn($item) => $item !== null && $item !== '' && !$this->isPlaceholder($item))
            ->unique()
            ->values()
            ->toArray();
    }

    private function generateFallbackRef(string $sheetName, int $line, array $data): string
    {
        $date = $data['date_du'] ?: 'no-date';
        $service = $data['service_name'] ?: 'no-service';
        $start = $data['point_depart'] ?: 'no-start';
        $end = $data['destination_name'] ?: 'no-end';

        return 'AUTO-' . md5($sheetName . '|' . $line . '|' . $date . '|' . $service . '|' . $start . '|' . $end);
    }

    public function printSupplierClients(Request $request)
    {
        return $this->printToursBySupplier($request, 'client');
    }

    public function printSupplierVehicules(Request $request)
    {
        return $this->printToursBySupplier($request, 'vehicule');
    }

    private function printToursBySupplier(Request $request, string $type)
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $dateFrom = $request->filled('date_du')
            ? Carbon::parse($request->date_du)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->filled('date_au')
            ? Carbon::parse($request->date_au)->endOfDay()
            : now()->endOfMonth();

        $title = $type === 'client'
            ? 'Tours by Client Supplier'
            : 'Tours by Vehicle Supplier';

        $query = Planning::with([
            'supplierVehicule',
            'driver',
            'guide',
            'service',
            'destination',
            'vehicule',
            'planningClients.client.supplierClient',
        ])->whereBetween('date_du', [
            $dateFrom->toDateString(),
            $dateTo->toDateString(),
        ]);

        if ($type === 'vehicule') {
            $query->whereNotNull('supplier_vehicule_id')
                ->orderBy('supplier_vehicule_id');
        }

        $plannings = $query
            ->orderBy('date_du')
            ->orderBy('heure')
            ->get();

        if ($type === 'client') {
            $groups = collect();

            foreach ($plannings as $planning) {
                $supplierNames = $planning->planningClients
                    ->map(fn($pc) => $pc->client?->supplierClient?->name)
                    ->filter()
                    ->unique()
                    ->values();

                if ($supplierNames->isEmpty()) {
                    $supplierNames = collect(['Without supplier']);
                }

                foreach ($supplierNames as $supplierName) {
                    if (!$groups->has($supplierName)) {
                        $groups[$supplierName] = collect();
                    }

                    $groups[$supplierName]->push($planning);
                }
            }
        } else {
            $groups = $plannings->groupBy(function ($planning) {
                return $planning->supplierVehicule?->name ?: 'Without supplier';
            });
        }

        $pdf = Pdf::loadView('pdf.planning-supplier-tours', [
            'title' => $title,
            'type' => $type,
            'groups' => $groups,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $fileName = $type === 'client'
            ? 'tours-by-client-supplier.pdf'
            : 'tours-by-vehicle-supplier.pdf';

        return $pdf->stream($fileName);
    }
}
