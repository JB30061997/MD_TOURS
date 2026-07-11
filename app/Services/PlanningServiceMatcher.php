<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlanningServiceMatcher
{
    public function recommend(Planning $planning, Collection $services): array
    {
        $context = $this->normalize(implode(' ', array_filter([
            $planning->point_depart,
            $planning->destination?->name,
            $planning->destination?->city,
            $planning->site,
            $planning->flight,
            $planning->notes,
            $planning->ref_dossier,
        ])));
        $departure = $this->normalize((string) $planning->point_depart);
        $arrival = $this->normalize(implode(' ', array_filter([
            $planning->destination?->name,
            $planning->destination?->city,
            $planning->site,
        ])));

        $ranked = $services->map(function (Service $service) use ($context, $departure, $arrival) {
            $name = $this->normalize($service->designation);
            $score = 0;
            $reasons = [];

            $serviceTokens = $this->tokens($name);
            $contextTokens = $this->tokens($context);
            $overlap = count(array_intersect($serviceTokens, $contextTokens));
            if ($overlap) {
                $score += min(45, $overlap * 15);
                $reasons[] = "{$overlap} mot(s) commun(s) avec le trajet";
            }

            $departureAirport = $this->containsAirport($departure);
            $arrivalAirport = $this->containsAirport($arrival);
            if ($arrivalAirport && $this->containsAny($name, ['departure', 'depart', 'aeroport'])) {
                $score += 80;
                $reasons[] = 'trajet vers un aéroport (départ)';
            }
            if ($departureAirport && $this->containsAny($name, ['arrival', 'arrivee', 'aeroport'])) {
                $score += 80;
                $reasons[] = 'trajet depuis un aéroport (arrivée)';
            }
            if ($arrivalAirport && $this->containsAny($name, ['arrival', 'arrivee']) && !$departureAirport) {
                $score -= 35;
            }
            if ($departureAirport && $this->containsAny($name, ['departure', 'depart']) && !$arrivalAirport) {
                $score -= 35;
            }

            foreach ([
                'circuit' => ['circuit', 'tour'],
                'excursion' => ['excursion'],
                'visite' => ['visite', 'visit'],
                'transfert' => ['transfert', 'transfer'],
            ] as $contextWord => $serviceWords) {
                if (str_contains($context, $contextWord) && $this->containsAny($name, $serviceWords)) {
                    $score += 70;
                    $reasons[] = "type de prestation « {$contextWord} » détecté";
                }
            }

            return [
                'service_id' => $service->id,
                'service_name' => $service->designation,
                'score' => max(0, min(100, $score)),
                'reasons' => array_values(array_unique($reasons)),
            ];
        })->sortByDesc('score')->values();

        $best = $ranked->first();
        $secondScore = (int) ($ranked->get(1)['score'] ?? 0);
        $score = (int) ($best['score'] ?? 0);
        $margin = $score - $secondScore;
        $confidence = $score >= 75 && $margin >= 20
            ? 'high'
            : ($score >= 45 && $margin >= 10 ? 'medium' : 'low');

        return [
            'service_id' => $best['service_id'] ?? null,
            'service_name' => $best['service_name'] ?? null,
            'reason' => $best && $best['reasons']
                ? implode('; ', $best['reasons'])
                : 'aucune correspondance suffisamment explicite',
            'confidence' => $confidence,
            'score' => $score,
            'alternatives' => $ranked->filter(fn ($item) => $item['score'] > 0)->take(5)->all(),
        ];
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function tokens(string $value): array
    {
        $ignored = ['de', 'du', 'des', 'la', 'le', 'les', 'a', 'au', 'aux', 'et', 'the', 'to', 'from', 'service'];

        return collect(explode(' ', $value))
            ->filter(fn ($token) => strlen($token) >= 3 && !in_array($token, $ignored, true))
            ->unique()->values()->all();
    }

    private function containsAirport(string $value): bool
    {
        return $this->containsAny($value, ['aeroport', 'airport', 'menara', 'rak', 'cmn', 'aga', 'terminal']);
    }

    private function containsAny(string $value, array $needles): bool
    {
        return collect($needles)->contains(fn ($needle) => str_contains($value, $needle));
    }
}
