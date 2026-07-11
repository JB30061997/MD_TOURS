<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PlanningMobileNotificationService
{
    public function __construct(private MobilePushNotificationService $push)
    {
    }

    public function notifyPlanningSaved(Planning $planning, string $event = 'saved'): array
    {
        try {
            $planning->loadMissing($this->relations());
            $users = $this->usersForPlanning($planning);

            if ($users->isEmpty()) {
                return ['users' => 0, 'sent' => 0, 'failed' => 0];
            }

            $title = $event === 'created' ? 'Nouveau service' : 'Service modifié';
            $body = $this->planningBody($planning, $event === 'created' ? 'Service ajouté' : 'Service mis à jour');

            $summary = $this->push->sendToUsers($users, $title, $body, [
                'type' => 'planning',
                'event' => $event,
                'planning_id' => $planning->id,
                'reference' => $planning->ref_dossier ?: '',
                'date' => optional($planning->date_du)->toDateString() ?: '',
            ]);

            return array_merge(['users' => $users->count()], $summary);
        } catch (\Throwable $exception) {
            Log::warning('Planning mobile notification failed.', [
                'planning_id' => $planning->id,
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);

            return ['users' => 0, 'sent' => 0, 'failed' => 1];
        }
    }

    public function notifyTodayServices(?Carbon $date = null, bool $dryRun = false): array
    {
        $date = ($date ?: now())->copy()->startOfDay();
        $plannings = $this->planningsForDate($date)->get();
        $byUser = [];

        foreach ($plannings as $planning) {
            foreach ($this->usersForPlanning($planning) as $user) {
                $byUser[$user->id]['user'] = $user;
                $byUser[$user->id]['plannings'][] = $planning;
            }
        }

        $sent = 0;
        $failed = 0;
        $tokens = 0;

        foreach ($byUser as $payload) {
            $count = count($payload['plannings']);
            $user = $payload['user'];

            if ($dryRun) {
                continue;
            }

            $summary = $this->push->sendToUsers([$user], 'Services du jour', "Vous avez {$count} service(s) aujourd'hui.", [
                'type' => 'daily_plannings',
                'date' => $date->toDateString(),
                'count' => $count,
            ]);

            $tokens += $summary['tokens'] ?? 0;
            $sent += $summary['sent'] ?? 0;
            $failed += $summary['failed'] ?? 0;
        }

        return [
            'date' => $date->toDateString(),
            'plannings' => $plannings->count(),
            'users' => count($byUser),
            'tokens' => $tokens,
            'sent' => $sent,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ];
    }

    private function usersForPlanning(Planning $planning): Collection
    {
        $planning->loadMissing($this->relations());

        $users = collect([
            $planning->driver?->user,
            $planning->guide?->user,
            $planning->supplierClient?->user,
            $planning->supplierVehicule?->user,
        ]);

        $clientSupplierUsers = $planning->planningClients
            ->map(fn ($planningClient) => $planningClient->client?->supplierClient?->user)
            ->filter();

        return $users
            ->merge($clientSupplierUsers)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();
    }

    private function planningsForDate(Carbon $date)
    {
        return Planning::query()
            ->with($this->relations())
            ->where(function ($query) use ($date) {
                $query->whereDate('date_du', $date)
                    ->orWhere(function ($rangeQuery) use ($date) {
                        $rangeQuery
                            ->whereDate('date_du', '<=', $date)
                            ->whereNotNull('date_au')
                            ->whereDate('date_au', '>=', $date);
                    });
            })
            ->orderBy('heure')
            ->orderBy('id');
    }

    private function planningBody(Planning $planning, string $prefix): string
    {
        $reference = $planning->ref_dossier ?: '#' . $planning->id;
        $date = $planning->date_du ? $planning->date_du->format('d/m/Y') : '-';
        $time = $planning->heure ? Carbon::parse($planning->heure)->format('H:i') : '--:--';
        $service = $planning->service?->designation;

        return trim("{$prefix}: Réf {$reference} - {$date} à {$time}" . ($service ? " - {$service}" : ''));
    }

    private function relations(): array
    {
        return [
            'service',
            'destination',
            'vehicule',
            'driver.user',
            'guide.user',
            'supplierClient.user',
            'supplierVehicule.user',
            'planningClients.client.supplierClient.user',
        ];
    }
}
