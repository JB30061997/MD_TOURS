<?php

namespace App\Services;

use App\Models\Planning;
use App\Support\RoadSheetDurationResolver;
use Carbon\Carbon;

class RoadSheetOperationalService
{
    public function syncStatus(Planning $planning): array
    {
        $summary = $this->summarize($planning);
        if ($planning->roadSheet) {
            $planning->roadSheet->update([
                'status' => match ($summary['global_status']) {
                    'completed' => 'renseignee',
                    'partial' => 'partielle',
                    default => 'a_completer',
                },
            ]);
        }

        return $summary;
    }

    public function summarize(Planning $planning): array
    {
        $planning->loadMissing(['service', 'roadSheet.lines']);
        $roadSheet = $planning->roadSheet;
        $totalDays = RoadSheetDurationResolver::resolve($planning);
        $lines = collect($roadSheet?->lines ?? [])->values();
        $usedLineIds = [];
        $days = collect(range(0, $totalDays - 1))->map(function (int $index) use ($planning, $lines, &$usedLineIds) {
            $expectedDate = $planning->date_du
                ? Carbon::parse($planning->date_du)->addDays($index)->toDateString()
                : null;
            $line = $lines->first(function ($candidate) use ($expectedDate, $index, $usedLineIds) {
                if (in_array($candidate->id, $usedLineIds, true)) {
                    return false;
                }

                return ($expectedDate && optional($candidate->date)->toDateString() === $expectedDate)
                    || (int) $candidate->sort_order === $index;
            });

            if ($line) {
                $usedLineIds[] = $line->id;
            }

            $completed = $this->lineIsCompleted($line);
            $departure = $line?->departure_kms;
            $arrival = $line?->arrival_kms;
            $distance = $completed
                ? max(0, (int) $arrival - (int) $departure)
                : (int) ($line?->distance ?? 0);

            return [
                'day_number' => $index + 1,
                'date' => optional($line?->date)->toDateString() ?: $expectedDate,
                'completed' => $completed,
                'status' => $completed ? 'completed' : 'missing',
                'status_label' => $completed ? 'Renseigné' : 'Non renseigné',
                'departure_kms' => $departure,
                'arrival_kms' => $arrival,
                'distance' => $distance,
                'gasoline' => (float) ($line?->gasoline ?? 0),
                'jawaz' => (float) ($line?->jawaz ?? 0),
                'other_expenses' => (float) ($line?->other_expenses ?? 0),
                'notes' => $line?->notes,
            ];
        });

        $completedDays = $days->where('completed', true)->count();
        $remainingDays = max(0, $totalDays - $completedDays);
        $circuitDistance = (int) $lines->sum(fn ($line) => $this->lineDistance($line));
        $preServiceDistance = $this->preServiceDistance($roadSheet);
        $status = match (true) {
            $completedDays === 0 => 'pending',
            $completedDays < $totalDays => 'partial',
            default => 'completed',
        };

        return [
            'total_days' => $totalDays,
            'completed_days' => $completedDays,
            'remaining_days' => $remainingDays,
            'progress' => (int) round(($completedDays / max(1, $totalDays)) * 100),
            'circuit_distance' => $circuitDistance,
            'pre_service_distance' => $preServiceDistance,
            'real_total_distance' => $circuitDistance + $preServiceDistance,
            'global_status' => $status,
            'global_status_label' => match ($status) {
                'completed' => 'Renseignée',
                'partial' => 'Partiellement renseignée',
                default => 'À compléter',
            },
            'pre_service' => [
                'origin' => $roadSheet?->pre_service_origin,
                'official_start' => $roadSheet?->start_city ?: $planning->point_depart,
                'odometer_start' => $roadSheet?->pre_service_odometer_start,
                'odometer_end' => $roadSheet?->pre_service_odometer_end,
                'distance' => $preServiceDistance,
                'note' => $roadSheet?->pre_service_note,
            ],
            'days' => $days->values()->all(),
        ];
    }

    private function lineIsCompleted($line): bool
    {
        return $line
            && $line->date !== null
            && $line->departure_kms !== null
            && $line->arrival_kms !== null
            && (int) $line->arrival_kms >= (int) $line->departure_kms;
    }

    private function lineDistance($line): int
    {
        if ($line->departure_kms !== null && $line->arrival_kms !== null && $line->arrival_kms >= $line->departure_kms) {
            return (int) $line->arrival_kms - (int) $line->departure_kms;
        }

        return max(0, (int) ($line->distance ?? 0));
    }

    private function preServiceDistance($roadSheet): int
    {
        if (! $roadSheet) {
            return 0;
        }

        if ($roadSheet->pre_service_odometer_start !== null
            && $roadSheet->pre_service_odometer_end !== null
            && $roadSheet->pre_service_odometer_end >= $roadSheet->pre_service_odometer_start) {
            return (int) $roadSheet->pre_service_odometer_end - (int) $roadSheet->pre_service_odometer_start;
        }

        return max(0, (int) $roadSheet->pre_service_km);
    }
}
