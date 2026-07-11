<?php

namespace App\Console\Commands;

use App\Models\Planning;
use App\Models\Service;
use App\Services\PlanningServiceMatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssignPlanningServices extends Command
{
    protected $signature = 'plannings:assign-services
        {--from=2026-05-01 : Première date incluse}
        {--to=2026-07-31 : Dernière date incluse}
        {--apply-report= : Rapport JSON dry-run à appliquer}
        {--yes : Confirme explicitement l’application des correspondances à haute confiance}';

    protected $description = 'Analyse puis affecte prudemment les services manquants des plannings';

    public function handle(PlanningServiceMatcher $matcher): int
    {
        if ($this->option('apply-report')) {
            return $this->applyReport((string) $this->option('apply-report'));
        }

        return $this->dryRun($matcher);
    }

    private function dryRun(PlanningServiceMatcher $matcher): int
    {
        try {
            $from = Carbon::parse((string) $this->option('from'))->toDateString();
            $to = Carbon::parse((string) $this->option('to'))->toDateString();
        } catch (\Throwable $e) {
            $this->error('Dates invalides: '.$e->getMessage());
            return self::FAILURE;
        }

        if ($from > $to) {
            $this->error('La date --from doit précéder --to.');
            return self::FAILURE;
        }

        $services = Service::query()->orderBy('designation')->get();
        if ($services->isEmpty()) {
            $this->warn('Aucun service disponible: aucune affectation ne peut être proposée.');
        }

        $plannings = Planning::query()
            ->with(['destination:id,name,city', 'supplierClient:id,name', 'supplierVehicule:id,name'])
            ->whereNull('service_id')
            ->whereBetween('date_du', [$from, $to])
            ->orderBy('date_du')->orderBy('heure')->orderBy('id')
            ->get();

        $rows = $plannings->map(function (Planning $planning) use ($matcher, $services) {
            $match = $matcher->recommend($planning, $services);

            return [
                'planning_id' => $planning->id,
                'planning_signature' => $this->signature($planning),
                'date' => $planning->date_du?->toDateString(),
                'heure' => $planning->heure?->format('H:i'),
                'ref_dossier' => $planning->ref_dossier,
                'point_depart' => $planning->point_depart,
                'destination' => trim(($planning->destination?->name ?? '').' '.($planning->destination?->city ?? '')),
                'site' => $planning->site,
                'flight' => $planning->flight,
                'service_id' => $match['service_id'],
                'service' => $match['service_name'],
                'reason' => $match['reason'],
                'confidence' => $match['confidence'],
                'score' => $match['score'],
                'will_apply' => $match['confidence'] === 'high',
                'alternatives' => $match['alternatives'],
            ];
        });

        $safe = $rows->where('will_apply', true)->values();
        $ambiguous = $rows->where('will_apply', false)->values();
        $timestamp = now()->format('Ymd_His');
        $directory = 'reports/planning-service-assignments';
        $reportPath = "{$directory}/dry-run_{$timestamp}.json";
        $ambiguousPath = "{$directory}/ambiguous_{$timestamp}.json";

        Storage::disk('local')->put($reportPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'period' => compact('from', 'to'),
            'mode' => 'dry-run',
            'assignments' => $safe->all(),
            'ambiguous_count' => $ambiguous->count(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Storage::disk('local')->put($ambiguousPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'period' => compact('from', 'to'),
            'plannings' => $ambiguous->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->table(
            ['Planning', 'Date/heure', 'Dossier', 'Service détecté', 'Raison', 'Confiance'],
            $rows->map(fn ($row) => [
                $row['planning_id'],
                trim($row['date'].' '.$row['heure']),
                $row['ref_dossier'] ?: '-',
                $row['service'] ?: 'Aucun',
                $row['reason'],
                strtoupper($row['confidence'])." ({$row['score']}%)",
            ])->all()
        );

        $this->newLine();
        $this->info("Dry-run terminé: {$rows->count()} planning(s), {$safe->count()} affectation(s) sûre(s), {$ambiguous->count()} ambiguë(s).");
        $this->line('Rapport applicable: '.Storage::disk('local')->path($reportPath));
        $this->line('Rapport ambigu: '.Storage::disk('local')->path($ambiguousPath));
        $this->warn('Aucune donnée n’a été modifiée. Relisez le rapport avant de lancer --apply-report avec --yes.');

        return self::SUCCESS;
    }

    private function applyReport(string $path): int
    {
        if (!$this->option('yes')) {
            $this->error('Ajoutez --yes après validation humaine du rapport.');
            return self::FAILURE;
        }

        $absolutePath = str_starts_with($path, '/') ? $path : Storage::disk('local')->path($path);
        if (!is_file($absolutePath)) {
            $this->error("Rapport introuvable: {$absolutePath}");
            return self::FAILURE;
        }

        $report = json_decode((string) file_get_contents($absolutePath), true);
        if (!is_array($report) || ($report['mode'] ?? null) !== 'dry-run' || !is_array($report['assignments'] ?? null)) {
            $this->error('Le fichier ne correspond pas à un rapport dry-run valide.');
            return self::FAILURE;
        }

        $applied = 0;
        $skipped = [];

        DB::transaction(function () use ($report, &$applied, &$skipped) {
            foreach ($report['assignments'] as $row) {
                $planning = Planning::query()->lockForUpdate()->find($row['planning_id'] ?? null);
                $service = Service::find($row['service_id'] ?? null);

                if (!$planning || !$service || $planning->service_id !== null) {
                    $skipped[] = ['planning_id' => $row['planning_id'] ?? null, 'reason' => 'planning/service absent ou service déjà affecté'];
                    continue;
                }
                if (($row['confidence'] ?? null) !== 'high' || ($row['will_apply'] ?? false) !== true) {
                    $skipped[] = ['planning_id' => $planning->id, 'reason' => 'correspondance non sûre'];
                    continue;
                }
                if (!hash_equals((string) ($row['planning_signature'] ?? ''), $this->signature($planning))) {
                    $skipped[] = ['planning_id' => $planning->id, 'reason' => 'planning modifié depuis le dry-run'];
                    continue;
                }

                $planning->update(['service_id' => $service->id]);
                $applied++;
            }
        });

        $this->info("Application terminée: {$applied} planning(s) mis à jour.");
        foreach ($skipped as $row) {
            $this->warn("Planning {$row['planning_id']}: ignoré ({$row['reason']}).");
        }

        return self::SUCCESS;
    }

    private function signature(Planning $planning): string
    {
        return hash('sha256', implode('|', [
            $planning->id,
            $planning->date_du?->toDateString(),
            $planning->heure?->format('H:i:s'),
            $planning->ref_dossier,
            $planning->point_depart,
            $planning->destination_id,
            $planning->site,
            $planning->flight,
            $planning->service_id,
            $planning->updated_at?->toIso8601String(),
        ]));
    }
}
